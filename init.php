<?php

if (!class_exists(RainLab\Blog\Plugin::class)) {
    class_alias(Winter\Blog\Plugin::class, RainLab\Blog\Plugin::class);

    class_alias(Winter\Blog\Classes\TagProcessor::class, RainLab\Blog\Classes\TagProcessor::class);

    class_alias(Winter\Blog\Components\Categories::class, RainLab\Blog\Components\Categories::class);
    class_alias(Winter\Blog\Components\Posts::class, RainLab\Blog\Components\Posts::class);
    class_alias(Winter\Blog\Components\Post::class, RainLab\Blog\Components\Post::class);
    class_alias(Winter\Blog\Components\RssFeed::class, RainLab\Blog\Components\RssFeed::class);

    class_alias(Winter\Blog\Controllers\Categories::class, RainLab\Blog\Controllers\Categories::class);
    class_alias(Winter\Blog\Controllers\Posts::class, RainLab\Blog\Controllers\Posts::class);

    class_alias(Winter\Blog\FormWidgets\BlogMarkdown::class, RainLab\Blog\FormWidgets\BlogMarkdown::class);
    class_alias(Winter\Blog\FormWidgets\MLBlogMarkdown::class, RainLab\Blog\FormWidgets\MLBlogMarkdown::class);

    class_alias(Winter\Blog\Models\Settings::class, RainLab\Blog\Models\Settings::class);
    class_alias(Winter\Blog\Models\PostImport::class, RainLab\Blog\Models\PostImport::class);
    class_alias(Winter\Blog\Models\Post::class, RainLab\Blog\Models\Post::class);
    class_alias(Winter\Blog\Models\Category::class, RainLab\Blog\Models\Category::class);
    class_alias(Winter\Blog\Models\PostExport::class, RainLab\Blog\Models\PostExport::class);
}
