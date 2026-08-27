<?php namespace Winter\Blog\Tests\Feature\Console;

use Artisan;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Winter\Blog\Console\ScaffoldCommand;
use Winter\Blog\Models\Category;
use Winter\Blog\Models\Post;
use Winter\Blog\Tests\BlogPluginTestCase;

class ScaffoldCommandTest extends BlogPluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // When Winter.Translate is present it makes Post translatable, so deleting a
        // post (exercised by --fresh) touches its tables. Migrate it if installed;
        // this is a no-op (throw = false) in a bare Blog CI where it is absent.
        $this->instantiatePlugin('Winter.Translate', false);

        // Plugin console commands are registered via ConsoleApplication::starting, which has
        // already fired by the time the test harness boots the plugin — so the command isn't
        // resolvable through Artisan here. Register it directly with the kernel for the test.
        $this->app->make(ConsoleKernel::class)->registerCommand(new ScaffoldCommand());
    }

    protected function scaffoldCategoryCount(): int
    {
        return Category::where('code', 'like', ScaffoldCommand::CODE_PREFIX . '%')->count();
    }

    protected function scaffoldPostCount(): int
    {
        return Post::where('slug', 'like', ScaffoldCommand::SLUG_PREFIX . '%')->count();
    }

    public function testCreatesDemoCategoriesAndPosts()
    {
        $this->assertSame(0, $this->scaffoldCategoryCount(), 'No scaffold categories should exist beforehand.');

        $exitCode = Artisan::call('scaffold:winter.blog');

        $this->assertSame(0, $exitCode);
        $this->assertSame(8, $this->scaffoldCategoryCount());
        $this->assertSame(29, $this->scaffoldPostCount());

        // The nested tree should have depth (a child category with a parent).
        $this->assertTrue(
            Category::where('code', 'like', ScaffoldCommand::CODE_PREFIX . '%')
                ->whereNotNull('parent_id')->exists(),
            'The scaffold should build a nested category tree.'
        );
    }

    public function testIsIdempotentWithoutFresh()
    {
        Artisan::call('scaffold:winter.blog');

        $exitCode = Artisan::call('scaffold:winter.blog');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('already exists', Artisan::output());
        $this->assertSame(8, $this->scaffoldCategoryCount(), 'A second run must not duplicate categories.');
        $this->assertSame(29, $this->scaffoldPostCount(), 'A second run must not duplicate posts.');
    }

    public function testFreshRecreatesTheData()
    {
        Artisan::call('scaffold:winter.blog');
        $firstIds = Category::where('code', 'like', ScaffoldCommand::CODE_PREFIX . '%')->pluck('id')->all();

        $exitCode = Artisan::call('scaffold:winter.blog', ['--fresh' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(8, $this->scaffoldCategoryCount());
        $this->assertSame(29, $this->scaffoldPostCount());

        $newIds = Category::where('code', 'like', ScaffoldCommand::CODE_PREFIX . '%')->pluck('id')->all();
        $this->assertEmpty(array_intersect($firstIds, $newIds), '--fresh should delete and recreate the categories.');
    }

    public function testRefusesToRunInProduction()
    {
        $this->app['env'] = 'production';

        $exitCode = Artisan::call('scaffold:winter.blog');

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, $this->scaffoldCategoryCount(), 'Nothing should be created in production.');

        $this->app['env'] = 'testing';
    }
}
