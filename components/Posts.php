<?php

namespace Winter\Blog\Components;

use Backend\Facades\BackendAuth;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Winter\Blog\Models\Category as BlogCategory;
use Winter\Blog\Models\Post as BlogPost;
use Winter\Blog\Models\Settings as BlogSettings;
use Winter\Storm\Database\Collection;
use Winter\Storm\Support\Facades\Event;

class Posts extends ComponentBase
{
    /**
     * A collection of posts to display
     */
    public LengthAwarePaginator|Collection|null $posts;

    /**
     * Parameter to use for the page number
     */
    public ?string $pageParam;

    /**
     * If the post list should be filtered by a category, the model to use
     */
    public ?BlogCategory $category;

    /**
     * Message to display when there are no messages
     */
    public ?string $noPostsMessage;

    /**
     * Reference to the page name for linking to posts
     */
    public ?string $postPage;

    /**
     * Reference to the page name for linking to categories
     */
    public ?string $categoryPage;

    /**
     * If the post list should be ordered by another attribute
     */
    public ?string $sortOrder;

    public function componentDetails()
    {
        return [
            'name'        => 'winter.blog::lang.settings.posts_title',
            'description' => 'winter.blog::lang.settings.posts_description',
        ];
    }

    public function defineProperties()
    {
        return [
            'pageNumber' => [
                'title'       => 'winter.blog::lang.settings.posts_pagination',
                'description' => 'winter.blog::lang.settings.posts_pagination_description',
                'type'        => 'string',
                'default'     => '{{ :page }}',
            ],
            'categoryFilter' => [
                'title'       => 'winter.blog::lang.settings.posts_filter',
                'description' => 'winter.blog::lang.settings.posts_filter_description',
                'type'        => 'string',
                'default'     => '',
            ],
            'postsPerPage' => [
                'title'             => 'winter.blog::lang.settings.posts_per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'winter.blog::lang.settings.posts_per_page_validation',
                'default'           => '10',
            ],
            'noPostsMessage' => [
                'title'             => 'winter.blog::lang.settings.posts_no_posts',
                'description'       => 'winter.blog::lang.settings.posts_no_posts_description',
                'type'              => 'string',
                'default'           => Lang::get('winter.blog::lang.settings.posts_no_posts_default'),
                'showExternalParam' => false,
            ],
            'sortOrder' => [
                'title'       => 'winter.blog::lang.settings.posts_order',
                'description' => 'winter.blog::lang.settings.posts_order_description',
                'type'        => 'dropdown',
                'default'     => 'published_at desc',
            ],
            'categoryPage' => [
                'title'       => 'winter.blog::lang.settings.posts_category',
                'description' => 'winter.blog::lang.settings.posts_category_description',
                'type'        => 'dropdown',
                'default'     => 'blog/category',
                'group'       => 'winter.blog::lang.settings.group_links',
            ],
            'postPage' => [
                'title'       => 'winter.blog::lang.settings.posts_post',
                'description' => 'winter.blog::lang.settings.posts_post_description',
                'type'        => 'dropdown',
                'default'     => 'blog/post',
                'group'       => 'winter.blog::lang.settings.group_links',
            ],
            'exceptPost' => [
                'title'             => 'winter.blog::lang.settings.posts_except_post',
                'description'       => 'winter.blog::lang.settings.posts_except_post_description',
                'type'              => 'string',
                'validationPattern' => '^[a-z0-9\-_,\s]+$',
                'validationMessage' => 'winter.blog::lang.settings.posts_except_post_validation',
                'default'           => '',
                'group'             => 'winter.blog::lang.settings.group_exceptions',
            ],
            'exceptCategories' => [
                'title'             => 'winter.blog::lang.settings.posts_except_categories',
                'description'       => 'winter.blog::lang.settings.posts_except_categories_description',
                'type'              => 'string',
                'validationPattern' => '^[a-z0-9\-_,\s]+$',
                'validationMessage' => 'winter.blog::lang.settings.posts_except_categories_validation',
                'default'           => '',
                'group'             => 'winter.blog::lang.settings.group_exceptions',
            ],
        ];
    }

    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getPostPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getSortOrderOptions()
    {
        $options = BlogPost::$allowedSortingOptions;

        foreach ($options as $key => $value) {
            $options[$key] = Lang::get($value);
        }

        return $options;
    }

    public function init()
    {
        Event::listen('translate.localePicker.translateParams', function ($page, $params, $oldLocale, $newLocale) {
            if (isset($params['slug'])) {
                $newParams = $params;
                $record = BlogCategory::transWhere('slug', $params['slug'], $oldLocale)->first();
                if ($record) {
                    $newParams['slug'] = $record->getAttributeTranslated('slug', $newLocale);
                    return $newParams;
                }
            }
        });
    }

    public function onRun()
    {
        $this->prepareVars();

        $this->category = $this->page['category'] = $this->loadCategory();
        $this->posts = $this->page['posts'] = $this->listPosts();

        /*
         * If the page number is not valid, redirect
         */
        if ($pageNumberParam = $this->paramName('pageNumber')) {
            $currentPage = $this->property('pageNumber');

            // If the page number has not been set, then default to page 1
            if (!$currentPage) {
                $this->setProperty('pageNumber', $currentPage = 1);
            }

            // Page number is not numeric or less than 1, then 404 as this is not a real page
            if (!is_numeric($currentPage) || $currentPage < 1) {
                $this->setStatusCode(404);
                return $this->controller->run('404');
            }

            // If the current page is bigger than the last page of pagination, then force the user back to the last page
            if ($currentPage > ($lastPage = $this->posts->lastPage()) && $currentPage > 1) {
                return Redirect::to($this->currentPageUrl([$pageNumberParam => $lastPage]));
            }
        }
    }

    protected function prepareVars()
    {
        $this->pageParam = $this->page['pageParam'] = $this->paramName('pageNumber');
        $this->noPostsMessage = $this->page['noPostsMessage'] = $this->property('noPostsMessage');
        $this->sortOrder = $this->property('sortOrder');

        /*
         * Page links
         */
        $this->postPage = $this->page['postPage'] = $this->property('postPage');
        $this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
    }

    protected function listPosts()
    {
        $category = $this->category ? $this->category->id : null;
        $categorySlug = $this->category ? $this->category->slug : null;

        /*
         * List all the posts, eager load their categories
         */
        $isPublished = !$this->checkEditor();

        $posts = BlogPost::with(['categories', 'featured_images'])->listFrontEnd([
            'page'             => $this->property('pageNumber'),
            'sort'             => $this->property('sortOrder'),
            'perPage'          => $this->property('postsPerPage'),
            'search'           => trim(input('search')),
            'category'         => $category,
            'published'        => $isPublished,
            'exceptPost'       => is_array($this->property('exceptPost'))
                ? $this->property('exceptPost')
                : preg_split('/,\s*/', $this->property('exceptPost'), -1, PREG_SPLIT_NO_EMPTY),
            'exceptCategories' => is_array($this->property('exceptCategories'))
                ? $this->property('exceptCategories')
                : preg_split('/,\s*/', $this->property('exceptCategories'), -1, PREG_SPLIT_NO_EMPTY),
        ]);

        /*
         * Add a "url" helper attribute for linking to each post and category
         */
        $posts->each(function ($post) use ($categorySlug) {
            $post->setUrl($this->postPage, $this->controller, ['category' => $categorySlug]);

            $post->categories->each(function ($category) {
                $category->setUrl($this->categoryPage, $this->controller);
            });
        });

        return $posts;
    }

    protected function loadCategory()
    {
        if (!$slug = $this->property('categoryFilter')) {
            return null;
        }

        $category = new BlogCategory();

        $category = $category->isClassExtendedWith('Winter.Translate.Behaviors.TranslatableModel')
            ? $category->transWhere('slug', $slug)
            : $category->where('slug', $slug);

        $category = $category->first();

        return $category ?: null;
    }

    protected function checkEditor()
    {
        $backendUser = BackendAuth::getUser();

        return $backendUser && $backendUser->hasAccess('winter.blog.access_posts') && BlogSettings::get('show_all_posts', true);
    }
}
