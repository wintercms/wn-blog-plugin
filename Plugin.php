<?php

namespace Winter\Blog;

use Backend\Facades\Backend;
use Backend\Models\UserRole;
use System\Classes\PluginBase;
use Winter\Blog\Classes\TagProcessor;
use Winter\Blog\Models\Category;
use Winter\Blog\Models\Post;
use Winter\Storm\Support\Facades\Event;

class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'winter.blog::lang.plugin.name',
            'description' => 'winter.blog::lang.plugin.description',
            'author'      => 'Winter CMS',
            'icon'        => 'icon-pencil',
            'homepage'    => 'https://github.com/wintercms/wn-blog-plugin',
            'replaces'    => ['RainLab.Blog' => '<= 1.7.0'],
        ];
    }

    /**
     * Registers the components provided by this plugin.
     */
    public function registerComponents(): array
    {
        return [
            \Winter\Blog\Components\Post::class       => 'blogPost',
            \Winter\Blog\Components\Posts::class      => 'blogPosts',
            \Winter\Blog\Components\Categories::class => 'blogCategories',
            \Winter\Blog\Components\RssFeed::class    => 'blogRssFeed',
        ];
    }

    /**
     * Registers the permissions provided by this plugin.
     */
    public function registerPermissions(): array
    {
        return [
            'winter.blog.manage_settings' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.manage_settings',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'winter.blog.access_posts' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.access_posts',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'winter.blog.access_categories' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.access_categories',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'winter.blog.access_other_posts' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.access_other_posts',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'winter.blog.access_import_export' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.access_import_export',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'winter.blog.access_publish' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.access_publish',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
        ];
    }

    /**
     * Registers the backend navigation items provided by this plugin.
     */
    public function registerNavigation(): array
    {
        return [
            'blog' => [
                'label'       => 'winter.blog::lang.blog.menu_label',
                'url'         => Backend::url('winter/blog/posts'),
                'icon'        => 'icon-pencil',
                'iconSvg'     => 'plugins/winter/blog/assets/images/blog-icon.svg',
                'permissions' => ['winter.blog.*'],
                'order'       => 300,

                'sideMenu' => [
                    'new_post' => [
                        'label'       => 'winter.blog::lang.posts.new_post',
                        'icon'        => 'icon-plus',
                        'url'         => Backend::url('winter/blog/posts/create'),
                        'permissions' => ['winter.blog.access_posts'],
                    ],
                    'posts' => [
                        'label'       => 'winter.blog::lang.blog.posts',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('winter/blog/posts'),
                        'permissions' => ['winter.blog.access_posts'],
                    ],
                    'categories' => [
                        'label'       => 'winter.blog::lang.blog.categories',
                        'icon'        => 'icon-list-ul',
                        'url'         => Backend::url('winter/blog/categories'),
                        'permissions' => ['winter.blog.access_categories'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Registers the settings provided by this plugin.
     */
    public function registerSettings(): array
    {
        return [
            'blog' => [
                'label' => 'winter.blog::lang.blog.menu_label',
                'description' => 'winter.blog::lang.blog.settings_description',
                'category' => 'winter.blog::lang.blog.menu_label',
                'icon' => 'icon-pencil',
                'class' => 'Winter\Blog\Models\Settings',
                'order' => 500,
                'keywords' => 'blog post category',
                'permissions' => ['winter.blog.manage_settings'],
            ],
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register(): void
    {
        /*
         * Register the image tag processing callback
         */
        TagProcessor::instance()->registerCallback(function ($input, $preview) {
            if (!$preview) {
                return $input;
            }

            return preg_replace(
                '|\<img src="image" alt="([0-9]+)"([^>]*)\/>|m',
                '<span class="image-placeholder" data-index="$1">
                    <span class="upload-dropzone">
                        <span class="label">Click or drop an image...</span>
                        <span class="indicator"></span>
                    </span>
                </span>',
                $input
            );
        });
    }

    /**
     * Boot method, called when the plugin is first booted.
     */
    public function boot(): void
    {
        $this->extendWinterPagesPlugin();
    }

    /**
     * Extends the Winter.Pages plugin
     */
    protected function extendWinterPagesPlugin(): void
    {
        /*
         * Register menu items for the Winter.Pages plugin
         */
        Event::listen('pages.menuitem.listTypes', function () {
            return [
                'blog-category'       => 'winter.blog::lang.menuitem.blog_category',
                'all-blog-categories' => 'winter.blog::lang.menuitem.all_blog_categories',
                'blog-post'           => 'winter.blog::lang.menuitem.blog_post',
                'all-blog-posts'      => 'winter.blog::lang.menuitem.all_blog_posts',
                'category-blog-posts' => 'winter.blog::lang.menuitem.category_blog_posts',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function ($type) {
            switch ($type) {
                case 'blog-category':
                case 'all-blog-categories':
                    return Category::getMenuTypeInfo($type);
                case 'blog-post':
                case 'all-blog-posts':
                case 'category-blog-posts':
                    return Post::getMenuTypeInfo($type);
            }
        });

        Event::listen('pages.menuitem.resolveItem', function ($type, $item, $url, $theme) {
            switch ($type) {
                case 'blog-category':
                case 'all-blog-categories':
                    return Category::resolveMenuItem($item, $url, $theme);
                case 'blog-post':
                case 'all-blog-posts':
                case 'category-blog-posts':
                    return Post::resolveMenuItem($item, $url, $theme);
            }
        });
    }
}
