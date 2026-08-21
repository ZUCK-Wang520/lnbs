<?php declare(strict_types=1);
$rows = $items ?? [];
$ok = !empty($tableOk);
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>消息</span>
</nav>

<h1 style="margin:0 0 1rem;font-size:1.35rem;">消息</h1>

<?php if (!$ok) : ?>
  <p class="muted">回复通知功能需执行数据库脚本 <code>public/database/migration_topic_reply_notifications.sql</code> 后启用。</p>
<?php elseif (empty($rows)) : ?>
  <p class="muted">暂无通知。当他人回复你发布的主题时，会在这里显示。</p>
<?php else : ?>
  <?php
    $unread = 0;
    foreach ($rows as $r) {
        if (empty($r['read_at'])) {
            $unread++;
        }
    }
  ?>
  <?php if ($unread > 0) : ?>
    <form method="post" action="<?= h(url('/messages/mark-all-read')) ?>" class="inline-form" style="margin:0 0 1rem;">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <button type="submit" class="btn btn-ghost btn-sm">全部标为已读</button>
    </form>
  <?php endif; ?>
  <ul class="messages-list" style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:0.5rem;">
    <?php foreach ($rows as $r) : ?>
      <?php
        $isUnread = empty($r['read_at']);
        $tid = (int) $r['topic_id'];
        $pid = (int) $r['post_id'];
        $href = url('/topic/' . $tid) . '#post-' . $pid;
      ?>
      <li>
        <a href="<?= h($href) ?>" class="card messages-item" style="display:block;padding:0.85rem 1rem;text-decoration:none;color:inherit;<?= $isUnread ? 'border-left:3px solid var(--accent, #5b8cff);' : '' ?>">
          <div style="display:flex;justify-content:space-between;align-items:baseline;gap:0.75rem;flex-wrap:wrap;">
            <span style="font-weight:<?= $isUnread ? '600' : '500' ?>;"><?= h((string) $r['topic_title']) ?></span>
            <time class="muted" style="font-size:0.82rem;white-space:nowrap;"><?= h((string) $r['created_at']) ?></time>
          </div>
          <p class="muted" style="margin:0.35rem 0 0;font-size:0.88rem;">有人回复了你的主题<?= $isUnread ? ' · 未读' : '' ?></p>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
