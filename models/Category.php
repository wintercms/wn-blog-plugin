<?php

namespace Winter\Blog\Models;

use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use Winter\Pages\Classes\MenuItem;
use Winter\Sitemap\Classes\DefinitionItem;
use Winter\Storm\Database\Model;
use Winter\Storm\Support\Facades\URL;
use Winter\Storm\Support\Str;

class Category extends Model
{
    use \Winter\Blog\Traits\Urlable;
    use \Winter\Storm\Database\Traits\NestedTree;
    use \Winter\Storm\Database\Traits\Validation;

    public $table = 'winter_blog_categories';
    public $implement = ['@Winter.Translate.Behaviors.TranslatableModel'];

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required',
        'slug' => 'required|between:3,64|unique',
        'code' => 'nullable|unique',
    ];

    /**
     * @var array Attributes that support translation, if available.
     */
    public $translatable = [
        'name',
        'description',
        ['slug', 'index' => true],
    ];

    protected $guarded = [];

    public $belongsToMany = [
        'posts' => [
            Post::class,
            'table' => 'winter_blog_posts_categories',
            'order' => 'published_at desc',
            'scope' => 'isPublished',
        ],
        'posts_count' => [
            Post::class,
            'table' => 'winter_blog_posts_categories',
            'scope' => 'isPublished',
            'count' => true,
        ],
    ];

    public function beforeValidate()
    {
        // Generate a URL slug for this model
        if (!$this->exists && !$this->slug) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function afterDelete()
    {
        $this->posts()->detach();
    }

    /**
     * Returns the number of posts in this category
     */
    public function getPostCountAttribute(): int
    {
        return optional($this->posts_count->first())->count ?? 0;
    }

    /**
     * Returns the number of posts in this and nested categories
     */
    public function getNestedPostCount(): int
    {
        return $this->post_count + $this->children->sum(function ($category) {
            return $category->getNestedPostCount();
        });
    }

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

        if (!class_exists(Theme::class)) {
            return $result;
        }

        if ($type == 'blog-category') {
            $result = [
                'references'   => self::listSubCategoryOptions(),
                'nesting'      => true,
                'dynamicItems' => true,
            ];
        }

        if ($type == 'all-blog-categories') {
            $result = [
                'dynamicItems' => true,
            ];
        }

        if ($result) {
            $theme = Theme::getActiveTheme();

            $pages = CmsPage::listInTheme($theme, true);
            $cmsPages = [];
            foreach ($pages as $page) {
                if (!$page->hasComponent('blogPosts')) {
                    continue;
                }

                /*
                 * Component must use a category filter with a routing parameter
                 * eg: categoryFilter = "{{ :somevalue }}"
                 */
                $properties = $page->getComponentProperties('blogPosts');
                if (!isset($properties['categoryFilter']) || !preg_match('/{{\s*:/', $properties['categoryFilter'])) {
                    continue;
                }

                $cmsPages[] = $page;
            }

            $result['cmsPages'] = $cmsPages;
        }

        return $result;
    }

    protected static function listSubCategoryOptions()
    {
        $category = self::getNested();

        $iterator = function ($categories) use (&$iterator) {
            $result = [];

            foreach ($categories as $category) {
                if (!$category->children) {
                    $result[$category->id] = $category->name;
                } else {
                    $result[$category->id] = [
                        'title' => $category->name,
                        'items' => $iterator($category->children),
                    ];
                }
            }

            return $result;
        };

        return $iterator($category);
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

        if ($item->type == 'blog-category') {
            // Attempt to get the category record for a specific category menu item
            if (!$item->reference) {
                return null;
            }
            $category = self::find($item->reference);
            if (!$category) {
                return null;
            }

            $pageUrl = $category->getUrl($cmsPage);
            if (!$pageUrl) {
                return null;
            }
            $pageUrl = Url::to($pageUrl);

            $result = [
                'url' => $pageUrl,
                'isActive' => $pageUrl === $currentUrl,
                'mtime' => $category->updated_at,
            ];

            $localizedUrls = $category->getLocalizedUrls($cmsPage);
            if (count($localizedUrls) > 1) {
                $result['alternateLinks'] = $localizedUrls;
            }

            if ($item->nesting) {
                $categories = $category->getNested();
                $iterator = function ($categories) use (&$iterator, &$item, &$theme, $currentUrl) {
                    $branch = [];

                    foreach ($categories as $category) {
                        $cmsPage = CmsPage::loadCached($theme, $item->cmsPage);
                        if (!$cmsPage) {
                            continue;
                        }

                        $branchItem = [];
                        $branchItem['url'] = $category->getUrl($cmsPage);
                        $branchItem['isActive'] = $branchItem['url'] === $currentUrl;
                        $branchItem['title'] = $category->name;
                        $branchItem['mtime'] = $category->updated_at;

                        $localizedUrls = $category->getLocalizedUrls($cmsPage);
                        if (count($localizedUrls) > 1) {
                            $branchItem['alternateLinks'] = $localizedUrls;
                        }

                        if ($category->children) {
                            $branchItem['items'] = $iterator($category->children);
                        }

                        $branch[] = $branchItem;
                    }

                    return $branch;
                };

                $result['items'] = $iterator($categories);
            }
        } elseif ($item->type == 'all-blog-categories') {
            $result = [
                'items' => [],
            ];

            $categories = self::orderBy('name')->get();
            foreach ($categories as $category) {
                $categoryItem = [
                    'title' => $category->name,
                    'url'   => Url::to($category->getUrl($cmsPage)),
                    'mtime' => $category->updated_at,
                ];

                $categoryItem['isActive'] = $categoryItem['url'] === $currentUrl;

                $localizedUrls = $category->getLocalizedUrls($cmsPage);
                if (count($localizedUrls) > 1) {
                    $categoryItem['alternateLinks'] = $localizedUrls;
                }

                $result['items'][] = $categoryItem;
            }
        }

        return $result;
    }

    /**
     * Get the URL parameters for this record, optionally using the provided CMS page.
     */
    public function getUrlParams(?CmsPage $page = null): array
    {
        $params = [
            'id'   => $this->id,
            'slug' => $this->slug,
        ];

        $paramName = $this->getParamNameFromComponentProperty($page, 'blogPosts', 'categoryFilter');
        if ($paramName) {
            $params[$paramName] = $this->slug;
        }

        return $params;
    }
}
