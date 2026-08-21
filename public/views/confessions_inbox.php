<?php declare(strict_types=1);
$f = $filter ?? 'inbox';
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>表白收件箱</span>
</nav>

<div class="confession-hub-nav">
  <a class="btn btn-primary btn-sm" href="<?= h(url('/confessions/new')) ?>">写表白</a>
  <a class="btn btn-ghost btn-sm" href="<?= h(url('/confessions/sent')) ?>">我发出的</a>
  <span class="confession-filter-tabs">
    <a href="<?= h(url('/confessions')) ?>" class="<?= $f === 'inbox' ? 'is-active' : '' ?>">收件箱</a>
    <a href="<?= h(url('/confessions', ['filter' => 'ignored'])) ?>" class="<?= $f === 'ignored' ? 'is-active' : '' ?>">已忽略</a>
  </span>
</div>

<?php if (empty($items)) : ?>
  <p class="muted"><?= $f === 'ignored' ? '暂无已忽略的表白。' : '还没有人向你表白，或已全部处理完毕。' ?></p>
<?php else : ?>
  <ul class="confession-list">
    <?php foreach ($items as $row) : ?>
      <?php
        $fromLabel = (int) $row['is_anonymous'] === 1 ? '匿名' : (string) $row['from_nickname'];
        $isUnread = (string) $row['status'] === 'unread';
      ?>
      <li class="confession-card card">
        <div class="confession-card-head">
          <span class="confession-from"><?= h($fromLabel) ?></span>
          <?php if ($isUnread) : ?><span class="badge badge-pin">未读</span><?php endif; ?>
          <?php if ((string) $row['status'] === 'ignored') : ?><span class="muted" style="font-size:0.8rem;">已忽略</span><?php endif; ?>
          <span class="muted confession-date"><?= h((string) $row['created_at']) ?></span>
        </div>
        <p class="confession-preview muted"><?= h(mb_substr((string) $row['body'], 0, 120)) ?><?= mb_strlen((string) $row['body']) > 120 ? '…' : '' ?></p>
        <div class="confession-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= h(url('/confession/' . (int) $row['id'])) ?>">查看全文</a>
          <?php if ($f === 'inbox' && (string) $row['status'] !== 'ignored') : ?>
            <form class="inline-form" method="post" action="<?= h(url('/confessions/ignore')) ?>" onsubmit="return confirm('确定忽略这条表白？仍可稍后在「已忽略」中查看。');">
              <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm">忽略</button>
            </form>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
