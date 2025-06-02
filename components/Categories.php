<?php

namespace Winter\Blog\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Illuminate\Support\Collection;
use Winter\Blog\Models\Category as BlogCategory;

class Categories extends ComponentBase
{
    /**
     * A collection of categories to display
     */
    public ?Collection $categories = null;

    /**
     * Reference to the page name for linking to categories.
     */
    public ?string $categoryPage = '';

    /**
     * Reference to the current category slug.
     */
    public ?string $currentCategorySlug = '';

    public function componentDetails(): array
    {
        return [
            'name'        => 'winter.blog::lang.settings.category_title',
            'description' => 'winter.blog::lang.settings.category_description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'slug' => [
                'title'       => 'winter.blog::lang.settings.category_slug',
                'description' => 'winter.blog::lang.settings.category_slug_description',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
            'displayEmpty' => [
                'title'       => 'winter.blog::lang.settings.category_display_empty',
                'description' => 'winter.blog::lang.settings.category_display_empty_description',
                'type'        => 'checkbox',
                'default'     => 0,
            ],
            'categoryPage' => [
                'title'       => 'winter.blog::lang.settings.category_page',
                'description' => 'winter.blog::lang.settings.category_page_description',
                'type'        => 'dropdown',
                'default'     => 'blog/category',
                'group'       => 'winter.blog::lang.settings.group_links',
            ],
        ];
    }

    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        $this->currentCategorySlug = $this->page['currentCategorySlug'] = $this->property('slug');
        $this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
        $this->categories = $this->page['categories'] = $this->loadCategories();
    }

    /**
     * Load all categories or, depending on the <displayEmpty> option, only those that have blog posts
     */
    protected function loadCategories(): Collection
    {
        $categories = BlogCategory::with('posts_count')->getNested();
        if (!$this->property('displayEmpty')) {
            $iterator = function ($categories) use (&$iterator) {
                return $categories->reject(function ($category) use (&$iterator) {
                    if ($category->getNestedPostCount() == 0) {
                        return true;
                    }
                    if ($category->children) {
                        $category->children = $iterator($category->children);
                    }
                    return false;
                });
            };
            $categories = $iterator($categories);
        }

        /*
         * Add a "url" helper attribute for linking to each category
         */
        return $this->linkCategories($categories);
    }

    /**
     * Sets the URL on each category according to the defined category page
     */
    protected function linkCategories(Collection $categories): Collection
    {
        return $categories->each(function ($category) {
            $category->setUrl($this->categoryPage, $this->controller);

            if ($category->children) {
                $this->linkCategories($category->children);
            }
        });
    }
}
