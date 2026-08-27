<?php namespace Winter\Blog\Console;

use Backend;
use Backend\Models\User;
use Carbon\Carbon;
use File;
use Illuminate\Console\Command;
use System\Models\File as FileModel;
use Winter\Blog\Models\Category;
use Winter\Blog\Models\Post;
use Winter\Storm\Support\Str;

/**
 * Scaffolds Winter.Blog demo data for local development and testing.
 *
 * Creates a small nested category tree (3 levels, incl. a deliberately long name)
 * plus a spread of posts — a full Markdown showcase, a very long title, a draft, a
 * scheduled/upcoming post, a many-categories post, and enough filler to paginate —
 * so every backend surface (lists, tree, filters, forms, the Markdown split
 * preview, relation picker, featured-image uploads and the dashboard report
 * widget) can be exercised. Mirrors the env-guarded, idempotent `scaffold:*`
 * pattern used elsewhere; scaffold rows are marked by a `scaffold-` slug/code
 * prefix so `--fresh` can scope its cleanup.
 */
class ScaffoldCommand extends Command
{
    protected $signature = 'scaffold:winter.blog
        {--fresh : Delete any existing scaffold data before recreating it}';

    protected $description = 'Scaffold Winter.Blog demo data (nested categories + a spread of varied posts) for local development/testing.';

    const CODE_PREFIX = 'scaffold-';
    const SLUG_PREFIX = 'scaffold-';

    public function handle(): int
    {
        // Never inject demo content into a production install.
        if ($this->getLaravel()->environment('production')) {
            $this->error('scaffold:winter.blog cannot run in the production environment.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->deleteExisting();
        }

        if (Category::where('code', 'like', self::CODE_PREFIX . '%')->exists()) {
            $this->warn('Blog scaffold data already exists. Use --fresh to recreate it.');

            return self::SUCCESS;
        }

        $categories = $this->createCategories();
        $this->info('Created ' . count($categories) . ' categories.');

        $postCount = $this->createPosts($categories);
        $this->info("Created {$postCount} posts.");

        $this->newLine();
        $this->line('Posts:      ' . Backend::url('winter/blog/posts'));
        $this->line('Categories: ' . Backend::url('winter/blog/categories'));

        return self::SUCCESS;
    }

    /**
     * Remove previously scaffolded posts (and their attachments/pivots) and
     * categories. Categories are removed deepest-first to keep the nested-tree
     * bounds consistent.
     */
    protected function deleteExisting(): void
    {
        $posts = Post::where('slug', 'like', self::SLUG_PREFIX . '%')->get();
        foreach ($posts as $post) {
            $post->featured_images()->delete();
            $post->content_images()->delete();
            $post->categories()->detach();
            $post->delete();
        }

        $categories = Category::where('code', 'like', self::CODE_PREFIX . '%')
            ->orderBy('nest_depth', 'desc')
            ->get();
        foreach ($categories as $category) {
            $category->posts()->detach();
            $category->delete();
        }

        if ($posts->isNotEmpty() || $categories->isNotEmpty()) {
            $this->info("Removed {$posts->count()} scaffold post(s) and {$categories->count()} category(ies).");
        }
    }

    /**
     * Build the nested category tree and return a handle => Category map used to
     * assign posts.
     */
    protected function createCategories(): array
    {
        $technology = $this->makeCategory('Technology');
        $web = $this->makeCategory('Web Development', $technology);
        $frontend = $this->makeCategory('Frontend', $web);
        $backend = $this->makeCategory('Backend', $web);
        $devops = $this->makeCategory('DevOps', $technology);

        $design = $this->makeCategory('Design');
        $longName = $this->makeCategory(
            'A deliberately very long category name used to test truncation, wrapping and '
            . 'overflow in the nested tree, the list, the posts filter dropdown and the relation picker',
            $design
        );

        $news = $this->makeCategory('News');

        return compact('technology', 'web', 'frontend', 'backend', 'devops', 'design', 'longName', 'news');
    }

    protected function makeCategory(string $name, ?Category $parent = null): Category
    {
        $handle = Str::slug(Str::limit($name, 48, ''));

        $category = new Category();
        $category->name = $name;
        $category->slug = self::SLUG_PREFIX . $handle;
        $category->code = self::CODE_PREFIX . $handle;
        if ($parent) {
            $category->parent_id = $parent->id;
        }
        $category->save();

        return $category;
    }

    protected function createPosts(array $cats): int
    {
        $author = User::first();
        $authorId = $author?->id;
        $richBody = File::get(__DIR__ . '/fixtures/rich_post.md');
        $count = 0;

        // 1. Full Markdown showcase — published, several categories, featured images.
        $this->makePost('The complete Markdown showcase', $richBody, [
            'published' => true,
            'published_at' => Carbon::now()->subDays(2),
            'user_id' => $authorId,
            'categories' => [$cats['technology'], $cats['web'], $cats['frontend']],
            'images' => 2,
        ]);
        $count++;

        // 2. Very long title.
        $this->makePost(
            'This is an intentionally and excessively long blog post title that exists purely to test how '
            . 'the backend list, breadcrumb, form header and tab labels handle text that simply refuses to '
            . 'end and keeps going well past any reasonable length',
            "A short body — the point of this post is the **title length**, not the content.",
            ['published' => true, 'published_at' => Carbon::now()->subDays(5), 'user_id' => $authorId, 'categories' => [$cats['news']]]
        );
        $count++;

        // 3. Draft (unpublished, no publish date) — exercises the "draft" list style + report widget.
        $this->makePost(
            'A work-in-progress draft',
            "This draft is **not published** yet.\n\n- still writing\n- todo: add images\n- todo: pick categories",
            ['published' => false, 'user_id' => $authorId, 'categories' => [$cats['design']]]
        );
        $count++;

        // 4. Scheduled/upcoming — future publish date drives the dashboard widget's "Upcoming".
        $this->makePost(
            'Scheduled: our upcoming announcement',
            "This one is scheduled for the future, so it appears under **Upcoming** in the dashboard report widget.",
            ['published' => true, 'published_at' => Carbon::now()->addDays(7), 'user_id' => $authorId, 'categories' => [$cats['news']]]
        );
        $count++;

        // 5. Many categories — stresses the relation column, the Categories tab and the picker.
        $this->makePost(
            'A post filed under many categories',
            "Assigned to every scaffold category to test the relation list column, the Categories tab and the relation picker with many selections.",
            ['published' => true, 'published_at' => Carbon::now()->subDays(8), 'user_id' => $authorId, 'categories' => array_values($cats)]
        );
        $count++;

        // 6. Filler for pagination (list is 25/page) + list density.
        $catList = array_values($cats);
        for ($i = 1; $i <= 24; $i++) {
            $this->makePost(
                "Sample blog post #{$i}",
                $this->fillerBody($i),
                [
                    'published' => true,
                    'published_at' => Carbon::now()->subDays(10 + $i),
                    'user_id' => $authorId,
                    'categories' => [$catList[$i % count($catList)]],
                    'images' => ($i % 6 === 0) ? 1 : 0,
                ]
            );
            $count++;
        }

        return $count;
    }

    protected function makePost(string $title, string $content, array $opts = []): Post
    {
        $post = new Post();
        $post->title = $title;
        $post->slug = self::SLUG_PREFIX . Str::slug(Str::limit($title, 48, '')) . '-' . strtolower(Str::random(5));
        $post->content = $content;
        $post->excerpt = Str::limit(trim(strip_tags($content)), 150);
        $post->published = $opts['published'] ?? false;
        if (!empty($opts['published_at'])) {
            $post->published_at = $opts['published_at'];
        }
        if (!empty($opts['user_id'])) {
            $post->user_id = $opts['user_id'];
        }
        $post->save();

        if (!empty($opts['categories'])) {
            $post->categories()->sync(collect($opts['categories'])->pluck('id')->all());
        }

        for ($n = 0; $n < ($opts['images'] ?? 0); $n++) {
            $this->attachImage($post, $n);
        }

        return $post;
    }

    protected function attachImage(Post $post, int $index): void
    {
        $sources = array_values(array_filter([
            base_path('themes/demo/assets/images/winter.png'),
            base_path('themes/demo/assets/images/theme-preview.png'),
            base_path('modules/backend/assets/images/wordmark.png'),
        ], fn ($path) => File::exists($path)));

        if (empty($sources)) {
            return;
        }

        $source = $sources[$index % count($sources)];

        $file = (new FileModel())->fromFile($source);
        $file->is_public = true;
        $file->save();

        $post->featured_images()->add($file);
    }

    protected function fillerBody(int $i): string
    {
        return "## Sample post {$i}\n\n"
            . "Scaffolded filler content for **post {$i}** — gives the list something to "
            . "paginate and the form something to render.\n\n"
            . "- point one\n- point two\n- point three\n\n"
            . "> A short blockquote for good measure.\n";
    }
}
