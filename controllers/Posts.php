<?php

namespace Winter\Blog\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Illuminate\Support\Facades\Lang;
use System\Classes\PluginManager;
use Winter\Blog\Models\Post;
use Winter\Blog\Models\Settings as BlogSettings;
use Winter\Storm\Support\Facades\Flash;

class Posts extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\ImportExportController::class,
    ];

    public $requiredPermissions = ['winter.blog.access_other_posts', 'winter.blog.access_posts'];

    protected $formLayout = 'fancy';

    public function index()
    {
        $this->vars['postsTotal'] = Post::count();
        $this->vars['postsPublished'] = Post::isPublished()->count();
        $this->vars['postsDrafts'] = $this->vars['postsTotal'] - $this->vars['postsPublished'];

        $this->asExtension('ListController')->index();
    }

    public function create()
    {
        BackendMenu::setContextSideMenu('new_post');

        $this->addCss('/plugins/winter/blog/assets/css/winter.blog-preview.css');
        $this->addJs('/plugins/winter/blog/assets/js/post-form.js');

        return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null)
    {
        $this->bodyClass = 'compact-container';
        $this->addCss('/plugins/winter/blog/assets/css/winter.blog-preview.css');
        $this->addJs('/plugins/winter/blog/assets/js/post-form.js');

        return $this->asExtension('FormController')->update($recordId);
    }

    public function export()
    {
        $this->addCss('/plugins/winter/blog/assets/css/winter.blog-export.css');

        return $this->asExtension('ImportExportController')->export();
    }

    public function listExtendQuery($query)
    {
        if (!$this->user->hasAnyAccess(['winter.blog.access_other_posts'])) {
            $query->where('user_id', $this->user->id);
        }
    }

    public function formExtendQuery($query)
    {
        if (!$this->user->hasAnyAccess(['winter.blog.access_other_posts'])) {
            $query->where('user_id', $this->user->id);
        }
    }

    public function formExtendModel($model)
    {
        if ($model->exists && !empty($model->slug) && $model->preview_page) {
            $model->setUrl($model->preview_page, (new \Cms\Classes\Controller()));
        }
    }

    public function formExtendFieldsBefore($widget)
    {
        if (!$model = $widget->model) {
            return;
        }
        if (!$model instanceof Post || $widget->isNested) {
            return;
        }
        $pluginManager = PluginManager::instance();

        $useRichEditor = BlogSettings::get('use_rich_editor', false);
        $useMlWidget = $pluginManager->exists('Winter.Translate');

        if ($useRichEditor) {
            $widget->tabs['fields']['content']['type'] = $useMlWidget ? 'Winter\Translate\FormWidgets\MLRichEditor' : 'richeditor';
        } elseif ($useMlWidget) {
            $widget->tabs['fields']['content']['type'] = 'Winter\Blog\FormWidgets\MLBlogMarkdown';
        }
    }

    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $postId) {
                if ((!$post = Post::find($postId)) || !$post->canEdit($this->user)) {
                    continue;
                }

                $post->delete();
            }

            Flash::success(Lang::get('winter.blog::lang.post.delete_success'));
        }

        return $this->listRefresh();
    }

    /**
     * {@inheritDoc}
     */
    public function listInjectRowClass($record, $definition = null)
    {
        if (!$record->published) {
            return 'safe disabled';
        }
    }

    public function formBeforeCreate($model)
    {
        $model->user_id = $this->user->id;
    }

    public function onRefreshPreview()
    {
        $data = post('Post');

        $previewHtml = Post::formatHtml($data['content'], true);

        return [
            'preview' => $previewHtml,
        ];
    }
}
