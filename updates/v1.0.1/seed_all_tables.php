<?php

namespace Winter\Blog\Updates;

use Carbon\Carbon;
use Winter\Blog\Models\Category;
use Winter\Blog\Models\Post;
use Winter\Storm\Database\Updates\Seeder;

class SeedAllTables extends Seeder
{
    public function run()
    {
        Post::extend(function ($model) {
            $model->setTable('rainlab_blog_posts');
        });

        Post::create([
            'title' => 'First blog post',
            'slug' => 'first-blog-post',
            'content' => '
This is your first ever **blog post**! It might be a good idea to update this post with some more relevant content.

You can edit this content by selecting **Blog** from the administration back-end menu.

*Enjoy the good times!*
            ',
            'excerpt' => 'The first ever blog post is here. It might be a good idea to update this post with some more relevant content.',
            'published_at' => Carbon::now(),
            'published' => true,
        ]);

        Category::extend(function ($model) {
            $model->setTable('rainlab_blog_categories');
        });

        Category::create([
            'name' => trans('winter.blog::lang.categories.uncategorized'),
            'slug' => 'uncategorized',
        ]);

        Post::extend(function ($model) {
            $model->setTable('winter_blog_posts');
        });

        Category::extend(function ($model) {
            $model->setTable('winter_blog_categories');
        });
    }
}
