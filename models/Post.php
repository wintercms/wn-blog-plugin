<?php

namespace Winter\Blog\Models;

use Backend\Facades\BackendAuth;
use Backend\Models\User;
use Carbon\Carbon;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Lang;
use System\Models\File;
use Winter\Blog\Classes\TagProcessor;
use Winter\Blog\Models\Settings as BlogSettings;
use Winter\Pages\Classes\MenuItem;
use Winter\Sitemap\Classes\DefinitionItem;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\NestedTreeScope;
use Winter\Storm\Exception\ValidationException;
use Winter\Storm\Support\Facades\Html;
use Winter\Storm\Support\Facades\Markdown;
use Winter\Storm\Support\Facades\URL;

/**
 * Class Post
 */
class Post extends Model
{
    use \Winter\Blog\Traits\Urlable;
    use \Winter\Storm\Database\Traits\Validation;

    public $table = 'winter_blog_posts';
    public $implement = ['@Winter.Translate.Behaviors.TranslatableModel'];

    /*
     * Validation
     */
    public $rules = [
        'title'   => 'required',
        'slug'    => ['required', 'regex:/^[a-z0-9\/\:_\-\*\[\]\+\?\|]*$/i', 'unique'],
        'content' => 'required',
        'excerpt' => '',
    ];

    /**
     * @var array Attributes that support translation, if available.
     */
    public $translatable = [
        'title',
        'content',
        'content_html',
        'excerpt',
        'metadata',
        ['slug', 'index' => true],
    ];

    /**
     * @var array Attributes that should be purged prior to saving.
     */
    protected $purgeable = ['url'];

    /**
     * @var array Attributes to be stored as JSON
     */
    protected $jsonable = ['metadata'];

    /**
     * The attributes that should be mutated to dates.
     * @var array
     */
    protected $dates = ['published_at'];

    /**
     * The attributes on which the post list can be ordered.
     * @var array
     */
    public static $allowedSortingOptions = [
        'title asc'         => 'winter.blog::lang.sorting.title_asc',
        'title desc'        => 'winter.blog::lang.sorting.title_desc',
        'created_at asc'    => 'winter.blog::lang.sorting.created_asc',
        'created_at desc'   => 'winter.blog::lang.sorting.created_desc',
        'updated_at asc'    => 'winter.blog::lang.sorting.updated_asc',
        'updated_at desc'   => 'winter.blog::lang.sorting.updated_desc',
        'published_at asc'  => 'winter.blog::lang.sorting.published_asc',
        'published_at desc' => 'winter.blog::lang.sorting.published_desc',
        'random'            => 'winter.blog::lang.sorting.random',
    ];

    /*
     * Relations
     */
    public $belongsTo = [
        'user' => [User::class],
    ];

    public $belongsToMany = [
        'categories' => [
            Category::class,
            'table' => 'winter_blog_posts_categories',
            'order' => 'name',
        ],
    ];

    public $attachMany = [
        'featured_images' => [
            File::class,
            'order' => 'sort_order',
        ],
        'content_images'  => [
            File::class,
        ],
    ];

    /**
     * @var array The accessors to append to the model's array form.
     */
    protected $appends = ['summary', 'has_summary'];

    public $preview = null;

    /**
     * {@inheritDoc}
     */
    public function __construct(array $attributes = [])
    {
        // Add the content processor for the blog as a local event so that it can be
        // bypassed by third parties if required.
        $this->bindEvent('model.beforeSave', function () {
            if (empty($this->user)) {
                $user = BackendAuth::getUser();
                if (!is_null($user)) {
                    $this->user = $user->id;
                }
            }
            $this->content_html = static::formatHtml($this->content);
        });

        parent::__construct($attributes);
    }

    /**
     * Limit visibility of the published-button
     *
     * @param       $fields
     * @param  null $context
     * @return void
     */
    public function filterFields($fields, $context = null)
    {
        if (!isset($fields->published, $fields->published_at)) {
            return;
        }

        $user = BackendAuth::getUser();

        if (!$user->hasAnyAccess(['winter.blog.access_publish'])) {
            $fields->published->hidden = true;
            $fields->published_at->hidden = true;
        } else {
            $fields->published->hidden = false;
            $fields->published_at->hidden = false;
        }
    }

    public function afterValidate()
    {
        if ($this->published && !$this->published_at) {
            throw new ValidationException([
                'published_at' => Lang::get('winter.blog::lang.post.published_validation'),
            ]);
        }
    }

    /**
     * Used to test if a certain user has permission to edit post,
     * returns TRUE if the user is the owner or has other posts access.
     */
    public function canEdit(User $user): bool
    {
        return ($this->user_id === $user->id) || $user->hasAnyAccess(['winter.blog.access_other_posts']);
    }

    public static function formatHtml($input, $preview = false)
    {
        $useRichEditor = BlogSettings::get('use_rich_editor', false);

        if ($useRichEditor) {
            $result = trim($input);
        } else {
            $result = Markdown::enableFootnotes()
                ->enableAttributes()
                ->enableTables()
                ->parse(trim($input));
        }

        // Check to see if the HTML should be cleaned from potential XSS
        $user = BackendAuth::getUser();
        if (!$user || !$user->hasAccess('backend.allow_unsafe_markdown')) {
            $result = Html::clean($result);
        }

        if ($preview) {
            $result = str_replace('<pre>', '<pre class="prettyprint">', $result);
        }

        $result = TagProcessor::instance()->processTags($result, $preview);

        return $result;
    }

    //
    // Scopes
    //

    public function scopeIsPublished($query)
    {
        return $query
            ->whereNotNull('published')
            ->where('published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<', Carbon::now())
        ;
    }

    /**
     * Lists posts for the frontend
     */
    public function scopeListFrontEnd($query, array $options = []): LengthAwarePaginator
    {
        /*
         * Default options
         */
        extract(array_merge([
            'page'             => 1,
            'perPage'          => 30,
            'sort'             => 'created_at',
            'categories'       => null,
            'exceptCategories' => null,
            'category'         => null,
            'search'           => '',
            'published'        => true,
            'exceptPost'       => null,
        ], $options));

        $searchableFields = ['title', 'slug', 'excerpt', 'content'];

        if ($published) {
            $query->isPublished();
        }

        /*
         * Except post(s)
         */
        if ($exceptPost) {
            $exceptPosts = (is_array($exceptPost)) ? $exceptPost : [$exceptPost];
            $exceptPostIds = [];
            $exceptPostSlugs = [];

            foreach ($exceptPosts as $exceptPost) {
                $exceptPost = trim($exceptPost);

                if (is_numeric($exceptPost)) {
                    $exceptPostIds[] = $exceptPost;
                } else {
                    $exceptPostSlugs[] = $exceptPost;
                }
            }

            if (count($exceptPostIds)) {
                $query->whereNotIn('id', $exceptPostIds);
            }
            if (count($exceptPostSlugs)) {
                $query->whereNotIn('slug', $exceptPostSlugs);
            }
        }

        /*
         * Sorting
         */
        if (in_array($sort, array_keys(static::$allowedSortingOptions))) {
            if ($sort == 'random') {
                $query->inRandomOrder();
            } else {
                @list($sortField, $sortDirection) = explode(' ', $sort);

                if (is_null($sortDirection)) {
                    $sortDirection = "desc";
                }

                $query->orderBy($sortField, $sortDirection);
            }
        }

        /*
         * Search
         */
        $search = trim($search);
        if (strlen($search)) {
            $query->searchWhere($search, $searchableFields);
        }

        /*
         * Categories
         */
        if ($categories !== null) {
            $categories = is_array($categories) ? $categories : [$categories];
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->withoutGlobalScope(NestedTreeScope::class)->whereIn('id', $categories);
            });
        }

        /*
         * Except Categories
         */
        if (!empty($exceptCategories)) {
            $exceptCategories = is_array($exceptCategories) ? $exceptCategories : [$exceptCategories];
            array_walk($exceptCategories, 'trim');

            $query->whereDoesntHave('categories', function ($q) use ($exceptCategories) {
                $q->withoutGlobalScope(NestedTreeScope::class)->whereIn('slug', $exceptCategories);
            });
        }

        /*
         * Category, including children
         */
        if ($category !== null) {
            $category = Category::find($category);

            $categories = $category->getAllChildrenAndSelf()->lists('id');
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->withoutGlobalScope(NestedTreeScope::class)->whereIn('id', $categories);
            });
        }

        return $query->paginate($perPage, $page);
    }

    /**
     * Allows filtering for specifc categories.
     * @param  Illuminate\Query\Builder  $query      QueryBuilder
     * @param  array                     $categories List of category ids
     * @return Illuminate\Query\Builder              QueryBuilder
     */
    public function scopeFilterCategories($query, $categories)
    {
        return $query->whereHas('categories', function ($q) use ($categories) {
            $q->withoutGlobalScope(NestedTreeScope::class)->whereIn('id', $categories);
        });
    }

    //
    // Summary / Excerpt
    //

    /**
     * Used by "has_summary", returns true if this post uses a summary (more tag).
     * @return boolean
     */
    public function getHasSummaryAttribute()
    {
        $more = '<!-- more -->';

        return (
            !!strlen(trim($this->excerpt)) ||
            strpos($this->content_html, $more) !== false ||
            strlen(Html::strip($this->content_html)) > 600
        );
    }

    /**
     * Used by "summary", if no excerpt is provided, generate one from the content.
     * Returns the HTML content before the <!-- more --> tag or a limited 600
     * character version.
     *
     * @return string
     */
    public function getSummaryAttribute()
    {
        $excerpt = $this->excerpt;
        if (strlen(trim($excerpt))) {
            return $excerpt;
        }

        $more = '<!-- more -->';
        if (strpos($this->content_html, $more) !== false) {
            $parts = explode($more, $this->content_html);

            return array_get($parts, 0);
        }

        return Html::limit($this->content_html, 600);
    }

    /**
     * Get the list of pages that can be used to display the post
     */
    public function getCmsPageOptions(): array
    {
        $result = [];

        if (!class_exists(Theme::class)) {
            return $result;
        }

        $theme = Theme::getActiveTheme();
        $pages = CmsPage::listInTheme($theme, true)->withComponent('blogPost', function ($component) {
            if (!preg_match('/{{\s*:/', $component->property('slug'))) {
                return false;
            }
            return true;
        });

        foreach ($pages as $page) {
            $result[$page->baseFileName] = $page->title;
        }

        return $result;
    }

    /**
     * Accessor for the $post->title attribute
     */
    public function getTitleAttribute($value)
    {
        if (!$this->is_published && !\App::runningInBackend()) {
            $value = '🔒 ' . $value;
        }
        return $value;
    }

    /**
     * Accessor for the $post->is_published attribute
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->published && $this->published_at && $this->published_at <= Carbon::now();
    }

    /**
     * Accessor for the $post->preview_page attribute
     */
    public function getPreviewPageAttribute(): ?string
    {
        $page = null;

        if (!empty($this->metadata['preview_page'])) {
            $page = $this->metadata['preview_page'];
        } else {
            $page = array_first(array_keys($this->getCmsPageOptions()));
        }

        return $page;
    }

    //
    // Next / Previous
    //

    /**
     * Apply a constraint to the query to find the nearest sibling
     *
     *     // Get the next post
     *     Post::applySibling()->first();
     *
     *     // Get the previous post
     *     Post::applySibling(-1)->first();
     *
     *     // Get the previous post, ordered by the ID attribute instead
     *     Post::applySibling(['direction' => -1, 'attribute' => 'id'])->first();
     *
     * @param       $query
     * @param array $options
     * @return
     */
    public function scopeApplySibling($query, $options = [])
    {
        if (!is_array($options)) {
            $options = ['direction' => $options];
        }

        extract(array_merge([
            'direction' => 'next',
            'attribute' => 'published_at',
        ], $options));

        $isPrevious = in_array($direction, ['previous', -1]);
        $directionOrder = $isPrevious ? 'asc' : 'desc';
        $directionOperator = $isPrevious ? '>' : '<';

        $query->where('id', '<>', $this->id);

        if (!is_null($this->$attribute)) {
            $query->where($attribute, $directionOperator, $this->$attribute);
        }

        return $query->orderBy($attribute, $directionOrder);
    }

    /**
     * Returns the next post, if available.
     * @return self
     */
    public function nextPost()
    {
        return self::isPublished()->applySibling()->first();
    }

    /**
     * Returns the previous post, if available.
     * @return self
     */
    public function previousPost()
    {
        return self::isPublished()->applySibling(-1)->first();
    }

    //
    // Menu helpers
    //

    /**
     * Handler for the pages.menuitem.getTypeInfo event.
     * Returns a menu item type information. The type information is returned as array
     * with the following elements:
     * - references - a list of the item type reference options. The options are returned in the
     *   ["key"] => "title" format for options that don't have sub-options, and in the format
     *   ["key"] => ["title"=>"Option title", "items"=>[...]] for options that have sub-options. Optional,
     *   required only if the menu item type requires references.
     * - nesting - Boolean value indicating whether the item type supports nested items. Optional,
     *   false if omitted.
     * - dynamicItems - Boolean value indicating whether the item type could generate new menu items.
     *   Optional, false if omitted.
     * - cmsPages - a list of CMS pages (objects of the Cms\Classes\Page class), if the item type requires a CMS page reference to
     *   resolve the item URL.
     */
    public static function getMenuTypeInfo(string $type): array
    {
        $result = [];

        if ($type == 'blog-post') {
            $references = [];

            $posts = self::select('id', 'title')->orderBy('title')->get();
            foreach ($posts as $post) {
                $references[$post->id] = $post->title;
            }

            $result = [
                'references'   => $references,
                'nesting'      => false,
                'dynamicItems' => false,
            ];
        }

        if ($type == 'all-blog-posts') {
            $result = [
                'dynamicItems' => true,
            ];
        }

        if ($type == 'category-blog-posts') {
            $references = [];

            $categories = Category::orderBy('name')->get();
            foreach ($categories as $category) {
                $references[$category->id] = $category->name;
            }

            $result = [
                'references'   => $references,
                'dynamicItems' => true,
            ];
        }

        if ($result) {
            if (!class_exists(Theme::class)) {
                return $result;
            }

            $theme = Theme::getActiveTheme();

            $pages = CmsPage::listInTheme($theme, true);
            $cmsPages = [];

            foreach ($pages as $page) {
                if (!$page->hasComponent('blogPost')) {
                    continue;
                }

                /*
                 * Component must use a categoryPage filter with a routing parameter and post slug
                 * eg: categoryPage = "{{ :somevalue }}", slug = "{{ :somevalue }}"
                 */
                $properties = $page->getComponentProperties('blogPost');
                if (!isset($properties['categoryPage']) || !preg_match('/{{\s*:/', $properties['slug'])) {
                    continue;
                }

                $cmsPages[] = $page;
            }

            $result['cmsPages'] = $cmsPages;
        }

        return $result;
    }

    /**
     * Handler for the pages.menuitem.resolveItem event.
     * Returns information about a menu item. The result is an array
     * with the following keys:
     * - url - the menu item URL. Not required for menu item types that return all available records.
     *   The URL should be returned relative to the website root and include the subdirectory, if any.
     *   Use the Url::to() helper to generate the URLs.
     * - isActive - determines whether the menu item is active. Not required for menu item types that
     *   return all available records.
     * - items - an array of arrays with the same keys (url, isActive, items) + the title key.
     *   The items array should be added only if the $item's $nesting property value is TRUE.
     *
     * @param DefinitionItem|MenuItem $item Specifies the menu item.
     */
    public static function resolveMenuItem(object $item, string $currentUrl, Theme $theme): ?array
    {
        $result = null;

        if (!class_exists(CmsPage::class)) {
            return $result;
        }

        // Items must have a reference to a CMS page
        if (!$item->cmsPage) {
            return null;
        }
        $cmsPage = CmsPage::loadCached($theme, $item->cmsPage);
        if (!$cmsPage) {
            return null;
        }

        if ($item->type == 'blog-post') {
            // Attempt to get the post record for a specific post menu item
            if (!$item->reference) {
                return null;
            }
            $post = self::find($item->reference);
            if (!$post) {
                return null;
            }

            $pageUrl = $post->getUrl($cmsPage);
            if (!$pageUrl) {
                return null;
            }
            $pageUrl = Url::to($pageUrl);

            $result = [
                'url' => $pageUrl,
                'isActive' => $pageUrl === $currentUrl,
                'mtime' => $post->updated_at,
            ];

            $localizedUrls = $post->getLocalizedUrls($cmsPage);
            if (count($localizedUrls) > 1) {
                $result['alternateLinks'] = $localizedUrls;
            }
        } elseif ($item->type == 'all-blog-posts') {
            $result = [
                'items' => [],
            ];

            $posts = self::isPublished()->orderBy('title')->get();
            foreach ($posts as $post) {
                $postItem = [
                    'title' => $post->title,
                    'url'   => Url::to($post->getUrl($cmsPage)),
                    'mtime' => $post->updated_at,
                ];

                $postItem['isActive'] = $postItem['url'] === $currentUrl;

                $localizedUrls = $post->getLocalizedUrls($cmsPage);
                if (count($localizedUrls) > 1) {
                    $postItem['alternateLinks'] = $localizedUrls;
                }

                $result['items'][] = $postItem;
            }
        } elseif ($item->type == 'category-blog-posts') {
            if (!$item->reference) {
                return null;
            }

            $category = Category::find($item->reference);
            if (!$category) {
                return null;
            }

            $result = [
                'items' => [],
            ];

            $query = self::isPublished()->orderBy('title');

            $categories = $category->getAllChildrenAndSelf()->lists('id');
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->withoutGlobalScope(NestedTreeScope::class)->whereIn('id', $categories);
            });

            $posts = $query->get();

            foreach ($posts as $post) {
                $postItem = [
                    'title' => $post->title,
                    'url'   => Url::to($post->getUrl($cmsPage)),
                    'mtime' => $post->updated_at,
                ];

                $postItem['isActive'] = $postItem['url'] === $currentUrl;

                $localizedUrls = $post->getLocalizedUrls($cmsPage);
                if (count($localizedUrls) > 1) {
                    $postItem['alternateLinks'] = $localizedUrls;
                }

                $result['items'][] = $postItem;
            }
        }

        return $result;
    }

    /**
     * Get the URL parameters for this record, optionally using the provided CMS page.
     */
    public function getUrlParams(?CmsPage $page = null): array
    {
        $firstCategory = $this->categories->first();
        $params = [
            'id'   => $this->id,
            'slug' => $this->slug,
            'category' => $firstCategory ? $firstCategory->slug : null,
        ];

        // Expose published year, month and day as URL parameters.
        if ($this->published) {
            $params['year']  = $this->published_at->format('Y');
            $params['month'] = $this->published_at->format('m');
            $params['day']   = $this->published_at->format('d');
        }

        $paramName = $this->getParamNameFromComponentProperty($page, 'blogPost', 'slug');
        if ($paramName) {
            $params[$paramName] = $this->slug;
        }

        return $params;
    }
}
