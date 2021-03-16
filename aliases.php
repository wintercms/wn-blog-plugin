<?php
/**
 * To allow compatibility with plugins that extend the original RainLab.Sitemap plugin, this will alias those classes to
 * use the new Winter.Sitemap classes.
 */
$aliases = [
    Winter\Blog\Plugin::class                     => 'RainLab\Blog\Plugin',
    Winter\Blog\Components\Categories::class      => 'RainLab\Blog\Components\Categories',
    Winter\Blog\Classes\TagProcessor::class       => 'RainLab\Blog\Classes\TagProcessor',
    Winter\Blog\Components\Posts::class           => 'RainLab\Blog\Components\Posts',
    Winter\Blog\Components\Post::class            => 'RainLab\Blog\Components\Post',
    Winter\Blog\Components\RssFeed::class         => 'RainLab\Blog\Components\RssFeed',
    Winter\Blog\Controllers\Categories::class     => 'RainLab\Blog\Controllers\Categories',
    Winter\Blog\Controllers\Posts::class          => 'RainLab\Blog\Controllers\Posts',
    Winter\Blog\FormWidgets\BlogMarkdown::class   => 'RainLab\Blog\FormWidgets\BlogMarkdown',
    Winter\Blog\FormWidgets\MLBlogMarkdown::class => 'RainLab\Blog\FormWidgets\MLBlogMarkdown',
    Winter\Blog\Models\Settings::class            => 'RainLab\Blog\Models\Settings',
    Winter\Blog\Models\PostImport::class          => 'RainLab\Blog\Models\PostImport',
    Winter\Blog\Models\Post::class                => 'RainLab\Blog\Models\Post',
    Winter\Blog\Models\Category::class            => 'RainLab\Blog\Models\Category',
    Winter\Blog\Models\PostExport::class          => 'RainLab\Blog\Models\PostExport',
];

foreach ($aliases as $original => $alias) {
    if (!class_exists($alias)) {
        class_alias($original, $alias);
    }
} 