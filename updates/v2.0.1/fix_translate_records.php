<?php

namespace Winter\Blog\Updates;

use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\DB;
use Winter\Storm\Support\Facades\Schema;

class FixTranslateRecords extends Migration
{
    public const MODELS = [
        'Category',
        'Post',
    ];

    public function up()
    {
        $this->fix_records('RainLab', 'Winter');
    }

    public function down()
    {
        $this->fix_records('Winter', 'RainLab');
    }

    public function fix_records($from, $to)
    {
        $tables = ['indexes', 'attributes'];

        if (Schema::hasTable('rainlab_translate_indexes')) {
            $tables = preg_filter('/^/', 'rainlab_translate_', $tables);
        } elseif (Schema::hasTable('rainlab_translate_indexes')) {
            $tables = preg_filter('/^/', 'rainlab_translate_', $tables);
        } else {
            return;
        }

        foreach ($tables as $table) {
            foreach (self::MODELS as $model) {
                $fromModel = $from . '\\Blog\\Models\\' . $model;
                $toModel = $to . '\\Blog\\Models\\' . $model;
                Db::table($table)
                    ->where('model_type', $fromModel)
                    ->update(['model_type' => $toModel]);
            }
        }
    }
}
