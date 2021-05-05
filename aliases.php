<?php

use Winter\Storm\Support\ClassLoader;

/**
 * To allow compatibility with plugins that extend the original RainLab.Blog plugin, this will alias those classes to
 * use the new Winter.Blog classes.
 */
$aliases = [
    Winter\Blog\Plugin::class                     => RainLab\Blog\Plugin::class,
    Winter\Blog\Components\Categories::class      => RainLab\Blog\Components\Categories::class,
    Winter\Blog\Classes\TagProcessor::class       => RainLab\Blog\Classes\TagProcessor::class,
    Winter\Blog\Components\Posts::class           => RainLab\Blog\Components\Posts::class,
    Winter\Blog\Components\Post::class            => RainLab\Blog\Components\Post::class,
    Winter\Blog\Components\RssFeed::class         => RainLab\Blog\Components\RssFeed::class,
    Winter\Blog\Controllers\Categories::class     => RainLab\Blog\Controllers\Categories::class,
    Winter\Blog\Controllers\Posts::class          => RainLab\Blog\Controllers\Posts::class,
    Winter\Blog\FormWidgets\BlogMarkdown::class   => RainLab\Blog\FormWidgets\BlogMarkdown::class,
    Winter\Blog\FormWidgets\MLBlogMarkdown::class => RainLab\Blog\FormWidgets\MLBlogMarkdown::class,
    Winter\Blog\Models\Settings::class            => RainLab\Blog\Models\Settings::class,
    Winter\Blog\Models\PostImport::class          => RainLab\Blog\Models\PostImport::class,
    Winter\Blog\Models\Post::class                => RainLab\Blog\Models\Post::class,
    Winter\Blog\Models\Category::class            => RainLab\Blog\Models\Category::class,
    Winter\Blog\Models\PostExport::class          => RainLab\Blog\Models\PostExport::class,
];

app(ClassLoader::class)->addAliases($aliases);
