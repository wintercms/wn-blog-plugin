<?php

namespace Winter\Blog\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Winter\Blog\Models\Post;
use Carbon\Carbon;

class Posts extends ReportWidgetBase
{
    public function render()
    {
        $this->loadData();
        return $this->makePartial('widget');
    }

    protected function loadAssets()
    {
        $this->addCss('css/posts.css');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'backend::lang.dashboard.widget_title_label',
                'default'           => 'winter.blog::lang.widgets.posts.title',
                'type'              => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'backend::lang.dashboard.widget_title_error',
            ]
        ];
    }

    protected function loadData()
    {
        $this->vars['latest'] = Post::isPublished()->first();
        $this->vars['drafts'] = Post::where('published', false)
            ->with('user')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        $this->vars['upcoming'] = Post::where('published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '>', Carbon::now())
            ->orderBy('published_at', 'asc')
            ->limit(5)
            ->get();
    }
}
