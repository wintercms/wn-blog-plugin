<div data-control="toolbar">
    <a
        href="<?= Backend::url('winter/blog/posts/create') ?>"
        class="btn btn-primary oc-icon-plus">
        <?= e(trans('winter.blog::lang.posts.new_post')) ?>
    </a>
    <button
        class="btn btn-default oc-icon-trash-o"
        disabled="disabled"
        onclick="$(this).data('request-data', {
            checked: $('.control-list').listWidget('getChecked')
        })"
        data-request="onDelete"
        data-request-confirm="<?= e(trans('winter.blog::lang.blog.delete_confirm')) ?>"
        data-trigger-action="enable"
        data-trigger=".control-list input[type=checkbox]"
        data-trigger-condition="checked"
        data-request-success="$(this).prop('disabled', true)"
        data-stripe-load-indicator>
        <?= e(trans('backend::lang.list.delete_selected')) ?>
    </button>

    <?php if ($this->user->hasAnyAccess(['winter.blog.access_import_export'])): ?>
        <div class="btn-group">
            <a
                href="<?= Backend::url('winter/blog/posts/export') ?>"
                class="btn btn-default oc-icon-download">
                <?= e(trans('winter.blog::lang.posts.export_post')) ?>
            </a>
            <a
                href="<?= Backend::url('winter/blog/posts/import') ?>"
                class="btn btn-default oc-icon-upload">
                <?= e(trans('winter.blog::lang.posts.import_post')) ?>
            </a>
        </div>
    <?php endif ?>
</div>
