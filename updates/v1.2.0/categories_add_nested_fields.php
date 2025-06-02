<?php

namespace Winter\Blog\Updates;

use Winter\Blog\Models\Category;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

class CategoriesAddNestedFields extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('rainlab_blog_categories', 'parent_id')) {
            return;
        }

        Schema::table('rainlab_blog_categories', function ($table) {
            $table->integer('parent_id')->unsigned()->index()->nullable();
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
        });

        Category::extend(function ($model) {
            $model->setTable('rainlab_blog_categories');
        });

        foreach (Category::all() as $category) {
            $category->setDefaultLeftAndRight();
            $category->save();
        }

        Category::extend(function ($model) {
            $model->setTable('winter_blog_categories');
        });
    }

    public function down()
    {
    }
}
