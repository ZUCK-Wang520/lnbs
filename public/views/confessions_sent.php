<?php declare(strict_types=1); ?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>我发出的表白</span>
</nav>

<div class="confession-hub-nav">
  <a class="btn btn-primary btn-sm" href="<?= h(url('/confessions/new')) ?>">写表白</a>
  <a class="btn btn-ghost btn-sm" href="<?= h(url('/confessions')) ?>">收件箱</a>
</div>

<?php if (empty($items)) : ?>
  <p class="muted">你还没有发出过表白。<a href="<?= h(url('/confessions/new')) ?>">写一条</a></p>
<?php else : ?>
  <ul class="confession-list">
    <?php foreach ($items as $row) : ?>
      <li class="confession-card card">
        <div class="confession-card-head">
          <span>发给 <strong><?= h((string) $row['to_nickname']) ?></strong><?php
            $tp = trim((string) ($row['to_phone'] ?? ''));
            if ($tp !== '') :
              ?> <span class="muted">(<?= h(mask_phone($tp)) ?>)</span><?php
            endif;
          ?></span>
          <span class="muted confession-date"><?= h((string) $row['created_at']) ?></span>
        </div>
        <p class="muted" style="margin:0.35rem 0;font-size:0.85rem;">
          <?= (int) $row['is_anonymous'] === 1 ? '对方看到：匿名' : '对方看到：你的昵称' ?>
          · 状态：<?= h((string) $row['status'] === 'unread' ? '对方未读' : ((string) $row['status'] === 'read' ? '对方已读' : '对方已忽略')) ?>
        </p>
        <p class="confession-preview"><?= h((string) $row['body']) ?></p>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
