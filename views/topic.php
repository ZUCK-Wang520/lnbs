<?php declare(strict_types=1); ?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <a href="<?= h(url('/board/' . $topic['board_slug'])) ?>"><?= h($topic['board_name']) ?></a>
  <span> / </span>
  <span>主题</span>
</nav>
<article class="topic-head">
  <h1>
    <?= h($topic['title']) ?>
    <?php if ((int) $topic['pinned'] === 1) : ?><span class="badge badge-pin">置顶</span><?php endif; ?>
    <?php if ((int) $topic['locked'] === 1) : ?><span class="badge badge-lock">锁定</span><?php endif; ?>
  </h1>
  <div class="topic-meta">
    <?= h($topic['author_nickname']) ?> · <?= h($topic['created_at']) ?>
  </div>
  <div class="body-text" style="margin-top:1rem;"><?= h($topic['body']) ?></div>
</article>

<div class="post-list">
  <?php foreach ($posts as $p) : ?>
    <article class="post" id="post-<?= (int) $p['id'] ?>">
      <div class="post-side">
        <strong><?= h($p['author_nickname']) ?></strong>
        <?= h($p['created_at']) ?>
      </div>
      <div>
        <div class="body-text"><?= h($p['body']) ?></div>
        <?php if ($current && ($current['role'] ?? '') === 'admin') : ?>
          <form class="inline-form" method="post" action="<?= h(url('/admin/posts/delete')) ?>" style="margin-top:0.75rem;" onsubmit="return confirm('删除该回复？');">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <input type="hidden" name="topic_id" value="<?= (int) $topic['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">删除回复</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php if ($current && (int) $current['banned'] === 0 && (int) $topic['locked'] === 0) : ?>
  <div class="form-panel" style="max-width:100%;margin-top:1.25rem;">
    <h2 style="margin-top:0;font-size:1.05rem;">发表回复</h2>
    <form method="post" action="<?= h(url('/topic/' . (int) $topic['id'] . '/reply')) ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="field">
        <label for="reply_body">内容</label>
        <textarea id="reply_body" name="body" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary">回复</button>
    </form>
  </div>
<?php elseif (!$current) : ?>
  <p class="muted" style="margin-top:1.25rem;"><a href="<?= h(url('/login')) ?>">登录</a> 后参与回复。</p>
<?php elseif ((int) $current['banned'] === 1) : ?>
  <p class="muted" style="margin-top:1.25rem;">您已被禁言，无法回复。</p>
<?php elseif ((int) $topic['locked'] === 1) : ?>
  <p class="muted" style="margin-top:1.25rem;">主题已锁定。</p>
<?php endif; ?>

<?php if ($current && ($current['role'] ?? '') === 'admin') : ?>
  <div class="toolbar" style="margin-top:1.5rem;">
    <form class="inline-form" method="post" action="<?= h(url('/admin/topics/delete')) ?>" onsubmit="return confirm('删除整个主题？');">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int) $topic['id'] ?>">
      <button type="submit" class="btn btn-danger">删除主题</button>
    </form>
  </div>
<?php endif; ?>
