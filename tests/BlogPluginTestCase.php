<?php namespace Winter\Blog\Tests;

if (class_exists('System\Tests\Bootstrap\PluginTestCase')) {
    class BaseTestCase extends \System\Tests\Bootstrap\PluginTestCase
    {
    }
} else {
    class BaseTestCase extends \PluginTestCase
    {
    }
}

abstract class BlogPluginTestCase extends BaseTestCase
{
    /**
     * @var array   Plugins to refresh between tests.
     */
    protected $refreshPlugins = [
        'Winter.Blog',
    ];
}
