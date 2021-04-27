<?php namespace Winter\Forum\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class AddUniqueRules extends Migration
{
    const UNIQUE_FIELDS = [
        'posts' => ['slug'],
        'categories' => ['slug']
    ];

    public function up()
    {
        foreach (array_keys(self::UNIQUE_FIELDS) as $table) {
            $this->switchIndexes($table, 'Index', 'Unique');
        }

        Schema::table('winter_blog_categories', function($table) {
           $table->unique('code');
        });
    }

    public function down()
    {
        foreach (array_keys(self::UNIQUE_FIELDS) as $table) {
            $this->switchIndexes($table, 'Unique', 'Index');
        }
    }

    public function switchIndexes($tableName, $fromType, $toType) {
        foreach (self::UNIQUE_FIELDS[$tableName] as $field) {
            try {
                $dropFunction = 'drop'.$fromType;
                $createFunction = strtolower($toType);

                Schema::table('winter_blog_'. $tableName, function($table) use ($field, $dropFunction, $createFunction)
                {
                    $table->$dropFunction([$field]);
                    $table->$createFunction($field);
                });
            } catch (\Exception $exception) {
                echo "Could not convert ${field} on the ${tableName} from ${fromType} to ${toType}" . PHP_EOL;
            }
        }
    }
}
