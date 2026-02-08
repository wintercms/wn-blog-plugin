<div class="report-widget" id="blog-posts">
    <h3><?= e(trans($this->property('title'))) ?></h3>

    <h4><?= e(trans('winter.blog::lang.widgets.posts.latest')) ?></h4>
    <?php if (!$latest): ?>
        <p><?= e(trans('winter.blog::lang.widgets.posts.no_latest_message')) ?></p>
    <?php else: ?>
    <p>
        <strong><a href="<?= Backend::url('winter/blog/posts/update/' . $latest->id) ?>"><?= e($latest->title) ?></a></strong> (<?= Backend::dateTime($latest->published_at, ['formatAlias' => 'dateTimeMin']) ?>)
    </p>

    <p>
        <strong><?= e(trans('winter.blog::lang.post.summary')) ?></strong>:
        <?= e($latest->summary) ?>
    </p>
    <?php endif ?>

    <h4><?= e(trans('winter.blog::lang.widgets.posts.upcoming')) ?></h4>
    <?php if (count($upcoming) == 0): ?>
        <p><?= e(trans('winter.blog::lang.widgets.posts.no_upcoming_message')) ?></p>
    <?php else: ?>
        <div class="control-status-list">
            <ul>
                <?php foreach ($upcoming as $post): ?>
                    <li>
                        <span class="status-icon success"><i class="icon-clock"></i></span>

                        <a href="<?= Backend::url('winter/blog/posts/update/' . $post->id) ?>" class="status-text success">
                            <?= e($post->title) ?>
                        </a>

                        <span class="status-label link">
                            <?= Backend::dateTime($post->published_at, ['formatAlias' => 'dateTimeMin']) ?>
                        </span>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <h4><?= e(trans('winter.blog::lang.widgets.posts.drafts')) ?></h4>
    <?php if (!count($drafts)): ?>
        <p><?= e(trans('winter.blog::lang.widgets.posts.no_drafts_message')) ?></p>
    <?php else: ?>
        <div class="control-status-list">
            <ul>
                <?php foreach ($drafts as $post): ?>
                    <li>
                        <span class="status-icon muted"><i class="icon-info"></i></span>
                        <a href="<?= Backend::url('winter/blog/posts/update/' . $post->id) ?>" class="status-text muted">
                            <?= e($post->title) ?>
                        </a>

                        <?php if ($post->user): ?>
                            <a href="<?= Backend::url('backend/users/preview/' . $post->user->id) ?>" class="status-label link">
                                <?= e($post->user->full_name) ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>
</div>
