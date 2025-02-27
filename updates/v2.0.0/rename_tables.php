<?php

namespace Winter\Blog\Updates;

use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\DB;
use Winter\Storm\Support\Facades\Schema;

class RenameTables extends Migration
{
    public const TABLES = [
        'categories',
        'posts',
        'posts_categories',
    ];

    public function up()
    {
        foreach (self::TABLES as $table) {
            $from = 'rainlab_blog_' . $table;
            $to = 'winter_blog_' . $table;

            if (Schema::hasTable($from) && !Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }

        Db::table('system_files')->where('attachment_type', 'RainLab\Blog\Models\Post')->update(['attachment_type' => 'Winter\Blog\Models\Post']);
        Db::table('system_settings')->where('item', 'rainlab_blog_settings')->update(['item' => 'winter_blog_settings']);
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            $from = 'winter_blog_' . $table;
            $to = 'rainlab_blog_' . $table;

            if (Schema::hasTable($from) && !Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }

        Db::table('system_files')->where('attachment_type', 'Winter\Blog\Models\Post')->update(['attachment_type' => 'RainLab\Blog\Models\Post']);
        Db::table('system_settings')->where('item', 'winter_blog_settings')->update(['item' => 'rainlab_blog_settings']);
    }
}
