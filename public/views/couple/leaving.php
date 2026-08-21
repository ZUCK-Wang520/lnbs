<?php declare(strict_types=1);
$coupleSubnavTitle = '留言祝福';
require __DIR__ . '/_subnav.php';
$wallPosts = is_array($wallPosts ?? null) ? $wallPosts : [];
?>
<div class="card couple-lg-page">
  <h1 class="couple-lg-page-title">留言祝福</h1>
  <p class="muted couple-lg-page-lead">只有你们两人可见的留言墙。</p>
  <form method="post" action="<?= h(url('/couple/wall')) ?>" class="couple-lg-form couple-lg-form--wall">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <label class="couple-lg-label" for="wall_body">新留言</label>
    <textarea id="wall_body" name="body" class="couple-lg-textarea" rows="3" maxlength="500" required placeholder="写点什么给 Ta…"></textarea>
    <button type="submit" class="btn btn-primary">发布</button>
  </form>
</div>
<?php if ($wallPosts === []) : ?>
  <p class="muted couple-lg-empty">还没有留言，做第一个吧～</p>
<?php else : ?>
  <ul class="couple-lg-wall-list">
    <?php foreach ($wallPosts as $w) : ?>
      <li class="card couple-lg-wall-item">
        <div class="couple-lg-wall-meta muted">
          <?= h((string) ($w['author_nickname'] ?? '')) ?> · <?= h((string) ($w['created_at'] ?? '')) ?>
        </div>
        <p class="couple-lg-wall-body"><?= nl2br(h((string) ($w['body'] ?? ''))) ?></p>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
