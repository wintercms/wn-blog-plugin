<?php namespace Winter\Blog\Updates;

use Db;
use Schema;
use Winter\Storm\Database\Updates\Migration;

class FixTranslateRecords extends Migration
{
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
        } else if (Schema::hasTable('rainlab_translate_indexes')) {
            $tables = preg_filter('/^/', 'rainlab_translate_', $tables);
        } else {
            return;
        }

        foreach ($tables as $table) {
            Db::table($table)
                ->where('model_type', $from . '\Blog\Models\Category')
                ->update(['model_type' => $to . '\Blog\Models\Category']);

            Db::table($table)
                ->where('model_type', $from . '\Blog\Models\Post')
                ->update(['model_type' => $to . '\Blog\Models\Post']);
        }
    }
}
