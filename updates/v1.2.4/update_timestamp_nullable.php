<?php

namespace Winter\Blog\Updates;

use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\DbDongle;

class UpdateTimestampsNullable extends Migration
{
    public function up()
    {
        DbDongle::disableStrictMode();

        DbDongle::convertTimestamps('rainlab_blog_posts');
        DbDongle::convertTimestamps('rainlab_blog_categories');
    }

    public function down()
    {
        // ...
    }
}
