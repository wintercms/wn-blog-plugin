<?php namespace Winter\Blog;

use Backend;
use Controller;
use Winter\Blog\Models\Post;
use System\Classes\PluginBase;
use Winter\Blog\Classes\TagProcessor;
use Winter\Blog\Models\Category;
use Event;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name'        => 'winter.blog::lang.plugin.name',
            'description' => 'winter.blog::lang.plugin.description',
            'author'      => 'Winter CMS',
            'icon'        => 'icon-pencil',
            'homepage'    => 'https://github.com/wintercms/wn-blog-plugin',
            'replaces'    => ['RainLab.Blog' => '<= 1.5.0'],
        ];
    }

    public function registerComponents()
    {
        return [
            'Winter\Blog\Components\Post'       => 'blogPost',
            'Winter\Blog\Components\Posts'      => 'blogPosts',
            'Winter\Blog\Components\Categories' => 'blogCategories',
            'Winter\Blog\Components\RssFeed'    => 'blogRssFeed'
        ];
    }

    public function registerPermissions()
    {
        return [
            'winter.blog.manage_settings' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.manage_settings'
            ],
            'winter.blog.access_posts' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.access_posts'
            ],
            'winter.blog.access_categories' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.access_categories'
            ],
            'winter.blog.access_other_posts' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.access_other_posts'
            ],
            'winter.blog.access_import_export' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.access_import_export'
            ],
            'winter.blog.access_publish' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'winter.blog::lang.blog.access_publish'
            ]
        ];
    }

    public function registerNavigation()
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
                        'permissions' => ['winter.blog.access_posts']
                    ],
                    'posts' => [
                        'label'       => 'winter.blog::lang.blog.posts',
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('winter/blog/posts'),
                        'permissions' => ['winter.blog.access_posts']
                    ],
                    'categories' => [
                        'label'       => 'winter.blog::lang.blog.categories',
                        'icon'        => 'icon-list-ul',
                        'url'         => Backend::url('winter/blog/categories'),
                        'permissions' => ['winter.blog.access_categories']
                    ]
                ]
            ]
        ];
    }

    public function registerSettings()
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
                'permissions' => ['winter.blog.manage_settings']
            ]
        ];
    }

    public function registerClassAliases()
    {
        /**
         * To allow compatibility with plugins that extend the original RainLab.Blog plugin, 
         * this will alias those classes to use the new Winter.Blog classes.
         */
        return [
            \Winter\Blog\Plugin::class                     => \RainLab\Blog\Plugin::class,
            \Winter\Blog\Components\Categories::class      => \RainLab\Blog\Components\Categories::class,
            \Winter\Blog\Classes\TagProcessor::class       => \RainLab\Blog\Classes\TagProcessor::class,
            \Winter\Blog\Components\Posts::class           => \RainLab\Blog\Components\Posts::class,
            \Winter\Blog\Components\Post::class            => \RainLab\Blog\Components\Post::class,
            \Winter\Blog\Components\RssFeed::class         => \RainLab\Blog\Components\RssFeed::class,
            \Winter\Blog\Controllers\Categories::class     => \RainLab\Blog\Controllers\Categories::class,
            \Winter\Blog\Controllers\Posts::class          => \RainLab\Blog\Controllers\Posts::class,
            \Winter\Blog\FormWidgets\BlogMarkdown::class   => \RainLab\Blog\FormWidgets\BlogMarkdown::class,
            \Winter\Blog\FormWidgets\MLBlogMarkdown::class => \RainLab\Blog\FormWidgets\MLBlogMarkdown::class,
            \Winter\Blog\Models\Settings::class            => \RainLab\Blog\Models\Settings::class,
            \Winter\Blog\Models\PostImport::class          => \RainLab\Blog\Models\PostImport::class,
            \Winter\Blog\Models\Post::class                => \RainLab\Blog\Models\Post::class,
            \Winter\Blog\Models\Category::class            => \RainLab\Blog\Models\Category::class,
            \Winter\Blog\Models\PostExport::class          => \RainLab\Blog\Models\PostExport::class,
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register()
    {
        /*
         * Register the image tag processing callback
         */
        TagProcessor::instance()->registerCallback(function($input, $preview) {
            if (!$preview) {
                return $input;
            }

            return preg_replace('|\<img src="image" alt="([0-9]+)"([^>]*)\/>|m',
                '<span class="image-placeholder" data-index="$1">
                    <span class="upload-dropzone">
                        <span class="label">Click or drop an image...</span>
                        <span class="indicator"></span>
                    </span>
                </span>',
            $input);
        });
    }

    public function boot()
    {
        /*
         * Register menu items for the Winter.Pages plugin
         */
        Event::listen('pages.menuitem.listTypes', function() {
            return [
                'blog-category'       => 'winter.blog::lang.menuitem.blog_category',
                'all-blog-categories' => 'winter.blog::lang.menuitem.all_blog_categories',
                'blog-post'           => 'winter.blog::lang.menuitem.blog_post',
                'all-blog-posts'      => 'winter.blog::lang.menuitem.all_blog_posts',
                'category-blog-posts' => 'winter.blog::lang.menuitem.category_blog_posts',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function($type) {
            if ($type == 'blog-category' || $type == 'all-blog-categories') {
                return Category::getMenuTypeInfo($type);
            }
            elseif ($type == 'blog-post' || $type == 'all-blog-posts' || $type == 'category-blog-posts') {
                return Post::getMenuTypeInfo($type);
            }
        });

        Event::listen('pages.menuitem.resolveItem', function($type, $item, $url, $theme) {
            if ($type == 'blog-category' || $type == 'all-blog-categories') {
                return Category::resolveMenuItem($item, $url, $theme);
            }
            elseif ($type == 'blog-post' || $type == 'all-blog-posts' || $type == 'category-blog-posts') {
                return Post::resolveMenuItem($item, $url, $theme);
            }
        });
    }
}
