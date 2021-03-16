<?php namespace Winter\Blog\Updates;

use Winter\Storm\Database\Updates\Migration;
use DbDongle;

class UpdateTimestampsNullable extends Migration
{
    public function up()
    {
        DbDongle::disableStrictMode();

        DbDongle::convertTimestamps('winter_blog_posts');
        DbDongle::convertTimestamps('winter_blog_categories');
    }

    public function down()
    {
        // ...
    }
}
