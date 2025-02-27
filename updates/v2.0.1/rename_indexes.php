<?php

namespace Winter\Blog\Updates;

use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

class RenameIndexes extends Migration
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

            $this->updateIndexNames($from, $to, $to);
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            $from = 'winter_blog_' . $table;
            $to = 'rainlab_blog_' . $table;

            $this->updateIndexNames($from, $to, $from);
        }
    }

    public function updateIndexNames($from, $to, $table)
    {
        Schema::table($table, function ($blueprint) use ($from, $to) {
            foreach ($this->getIndexes($blueprint) as $index) {
                if (is_object($index) ? $index->isPrimary() : $index['primary']) {
                    continue;
                }

                $old = is_object($index) ? $index->getName() : $index['name'];
                $new = str_replace($from, $to, $old);

                $blueprint->renameIndex($old, $new);
            }
        });
    }

    public function getIndexes($blueprint)
    {
        $connection = Schema::getConnection();
        $table = $blueprint->getTable();

        if (method_exists($connection, 'getDoctrineSchemaManager')) {
            $sm = $connection->getDoctrineSchemaManager();
            return $sm->listTableDetails($table)->getIndexes();
        } else {
            return $connection->getSchemaBuilder()->getIndexes($table);
        }
    }
}
