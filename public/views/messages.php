<?php declare(strict_types=1);
$rows = $items ?? [];
$ok = !empty($tableOk);
$coupleInvites = is_array($coupleInvites ?? null) ? $coupleInvites : [];
$coupleInviteCount = (int) ($coupleInviteCount ?? count($coupleInvites));
$chatUnreadOk = !empty($chatUnreadOk);
$chatUnreadThreads = is_array($chatUnreadThreads ?? null) ? $chatUnreadThreads : [];
$chatUnreadCount = (int) ($chatUnreadCount ?? 0);
$topicUnread = 0;
if ($ok) {
    foreach ($rows as $r) {
        if (empty($r['read_at'])) {
            $topicUnread++;
        }
    }
}
$hasMarkAll = $topicUnread > 0 || ($chatUnreadOk && $chatUnreadCount > 0);
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>消息</span>
</nav>

<h1 style="margin:0 0 1rem;font-size:1.35rem;">消息</h1>

<?php if ($hasMarkAll) : ?>
  <form method="post" action="<?= h(url('/messages/mark-all-read')) ?>" class="inline-form" style="margin:0 0 1rem;">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <button type="submit" class="btn btn-ghost btn-sm">全部标为已读</button>
  </form>
  <p class="muted" style="margin:-0.5rem 0 1rem;font-size:0.82rem;">标记主题回复与私信为已读；情侣邀请需在 <a href="<?= h(url('/couple')) ?>">情侣空间</a> 接受或拒绝后才会消失。</p>
<?php endif; ?>

<?php if ($chatUnreadOk && $chatUnreadCount > 0) : ?>
  <ul class="messages-list messages-list--chat" style="list-style:none;margin:0 0 1.25rem;padding:0;display:flex;flex-direction:column;gap:0.5rem;">
    <?php foreach ($chatUnreadThreads as $cht) : ?>
      <li>
        <a href="<?= h(url('/chat/with/' . (int) $cht['from_user_id'])) ?>" class="card messages-item messages-item--chat" style="display:block;padding:0.85rem 1rem;text-decoration:none;color:inherit;border-left:3px solid #60a5fa;">
          <div style="display:flex;justify-content:space-between;align-items:baseline;gap:0.75rem;flex-wrap:wrap;">
            <span style="font-weight:600;"><?= h((string) $cht['nickname']) ?> 发来私信</span>
            <time class="muted" style="font-size:0.82rem;white-space:nowrap;"><?= h((string) $cht['created_at']) ?></time>
          </div>
          <p class="muted" style="margin:0.35rem 0 0;font-size:0.88rem;">
            <?= h((string) $cht['body_preview']) ?>
            <?php if ((int) ($cht['unread_count'] ?? 0) > 1) : ?>
              · <span style="color:var(--accent2);"><?= (int) $cht['unread_count'] ?> 条未读</span>
            <?php else : ?>
              · <span style="color:var(--accent2);">未读</span>
            <?php endif; ?>
          </p>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php elseif (!$chatUnreadOk && chat_tables_ok()) : ?>
  <p class="muted" style="font-size:0.88rem;margin:0 0 1rem;">私信未读提示需执行 <code>public/database/migration_chat_read.sql</code>。</p>
<?php endif; ?>

<?php if ($coupleInviteCount > 0) : ?>
  <ul class="messages-list messages-list--couple" style="list-style:none;margin:0 0 1.25rem;padding:0;display:flex;flex-direction:column;gap:0.5rem;">
    <?php foreach ($coupleInvites as $cinv) : ?>
      <li>
        <a href="<?= h(url('/couple')) ?>" class="card messages-item messages-item--couple-invite" style="display:block;padding:0.85rem 1rem;text-decoration:none;color:inherit;border-left:3px solid #f472b6;">
          <div style="display:flex;justify-content:space-between;align-items:baseline;gap:0.75rem;flex-wrap:wrap;">
            <span style="font-weight:600;">情侣绑定邀请</span>
            <time class="muted" style="font-size:0.82rem;white-space:nowrap;"><?= h((string) ($cinv['created_at'] ?? '')) ?></time>
          </div>
          <p class="muted" style="margin:0.35rem 0 0;font-size:0.88rem;">
            <strong style="color:var(--text);font-weight:600;"><?= h((string) ($cinv['from_nickname'] ?? '')) ?></strong> 邀请你绑定情侣关系
            <?php if (trim((string) ($cinv['message'] ?? '')) !== '') : ?>
              ：<?= h(trim((string) $cinv['message'])) ?>
            <?php endif; ?>
            · <span style="color:var(--accent2);">前往情侣空间处理</span>
          </p>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php if (!$ok) : ?>
  <p class="muted"><?= $coupleInviteCount > 0 || $chatUnreadCount > 0 ? '上方为私信或情侣相关提醒。' : '' ?>回复通知功能需执行数据库脚本 <code>public/database/migration_topic_reply_notifications.sql</code> 后启用。</p>
<?php elseif (empty($rows)) : ?>
  <p class="muted"><?= ($coupleInviteCount > 0 || $chatUnreadCount > 0) ? '暂无主题回复通知。' : '暂无通知。' ?>当他人回复你发布的主题时，会显示在下方列表。</p>
<?php else : ?>
  <h2 style="margin:0 0 0.75rem;font-size:1.05rem;">主题回复</h2>
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
