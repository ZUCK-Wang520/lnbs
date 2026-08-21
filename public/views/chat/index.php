<?php declare(strict_types=1);
/** @var bool $tablesOk */
/** @var array|null $searchUser */
/** @var string $searchPhoneRaw */
/** @var list<array<string,mixed>> $incoming */
/** @var list<array<string,mixed>> $outgoing */
/** @var list<array<string,mixed>> $friends */
$u = auth_user();
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>私信</span>
</nav>

<h1 style="margin-top:0;">私信</h1>
<p class="muted" style="margin-top:-0.25rem;">通过手机号查找用户并添加好友，双方同意后即可聊天。</p>

<?php if (!$tablesOk) : ?>
  <p class="muted">请先执行数据库脚本 <code>public/database/migration_chat.sql</code> 后再使用本功能。</p>
<?php else : ?>

<section class="card" style="padding:1.25rem 1.35rem;margin-bottom:1.25rem;">
  <h2 style="margin:0 0 0.75rem;font-size:1.05rem;">按手机号查找</h2>
  <form method="get" action="<?= h(url('/chat')) ?>" class="chat-search-form" style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
    <input type="text" name="phone" value="<?= h($searchPhoneRaw) ?>" placeholder="11 位手机号" class="input" style="min-width:12rem;" autocomplete="tel">
    <button type="submit" class="btn btn-primary btn-sm">搜索</button>
  </form>
  <?php if ($searchPhoneRaw !== '') : ?>
    <?php if ($searchUser) : ?>
      <?php
        $sid = (int) $searchUser['id'];
        $state = chat_peer_state((int) $u['id'], $sid);
      ?>
      <div class="chat-search-result card" style="margin-top:1rem;padding:1rem;display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem;">
        <?php
          $av = user_avatar_public_url($searchUser['avatar'] ?? null);
          $nick = (string) $searchUser['nickname'];
          $ini = mb_substr($nick, 0, 1, 'UTF-8');
        ?>
        <?php if ($av) : ?>
          <img class="user-avatar-img" src="<?= h($av) ?>" alt="" width="40" height="40" loading="lazy">
        <?php else : ?>
          <span class="user-avatar-fallback" aria-hidden="true"><?= h($ini) ?></span>
        <?php endif; ?>
        <div style="flex:1;min-width:10rem;">
          <strong><?= h($nick) ?></strong>
          <p class="muted" style="margin:0.25rem 0 0;font-size:0.88rem;">用户 ID <?= (int) $sid ?></p>
        </div>
        <div class="chat-search-actions" style="display:flex;flex-wrap:wrap;gap:0.5rem;">
          <?php if (!empty($state['is_friend'])) : ?>
            <a class="btn btn-primary btn-sm" href="<?= h(url('/chat/with/' . $sid)) ?>">发私信</a>
          <?php elseif (!empty($state['out_pending'])) : ?>
            <span class="muted" style="font-size:0.9rem;">已申请，待对方同意</span>
          <?php elseif (!empty($state['in_pending'])) : ?>
            <span class="muted" style="font-size:0.9rem;">对方已申请加你，请在下方处理</span>
          <?php elseif (!empty($state['can_request'])) : ?>
            <form method="post" action="<?= h(url('/chat/friend-request')) ?>" class="inline-form">
              <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="to_user_id" value="<?= (int) $sid ?>">
              <button type="submit" class="btn btn-primary btn-sm">加好友</button>
            </form>
          <?php endif; ?>
          <a class="btn btn-ghost btn-sm" href="<?= h(url('/user/' . $sid . '/topics')) ?>">主页</a>
        </div>
      </div>
    <?php else : ?>
      <p class="muted" style="margin-top:0.75rem;margin-bottom:0;">未找到该手机号对应的用户，或不能与自己/系统账号互动。</p>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php if (!empty($incoming)) : ?>
<section class="card" style="padding:1.25rem 1.35rem;margin-bottom:1.25rem;">
  <h2 style="margin:0 0 0.75rem;font-size:1.05rem;">待处理的好友申请</h2>
  <ul style="list-style:none;padding:0;margin:0;">
    <?php foreach ($incoming as $row) : ?>
      <?php $fid = (int) $row['from_user_id']; ?>
      <li style="display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem;padding:0.65rem 0;border-bottom:1px solid var(--border-subtle, rgba(255,255,255,0.08));">
        <strong><?= h((string) $row['nickname']) ?></strong>
        <span class="muted" style="font-size:0.85rem;"><?= h((string) $row['created_at']) ?></span>
        <div style="display:flex;gap:0.5rem;margin-left:auto;">
          <form method="post" action="<?= h(url('/chat/friend-respond')) ?>" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="from_user_id" value="<?= (int) $fid ?>">
            <input type="hidden" name="decision" value="accept">
            <button type="submit" class="btn btn-primary btn-sm">同意</button>
          </form>
          <form method="post" action="<?= h(url('/chat/friend-respond')) ?>" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="from_user_id" value="<?= (int) $fid ?>">
            <input type="hidden" name="decision" value="decline">
            <button type="submit" class="btn btn-ghost btn-sm">拒绝</button>
          </form>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<?php if (!empty($outgoing)) : ?>
<section class="card" style="padding:1.25rem 1.35rem;margin-bottom:1.25rem;">
  <h2 style="margin:0 0 0.75rem;font-size:1.05rem;">等待对方通过</h2>
  <ul class="muted" style="margin:0;padding-left:1.2rem;">
    <?php foreach ($outgoing as $row) : ?>
      <li><?= h((string) $row['nickname']) ?> · <?= h((string) $row['created_at']) ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section class="card" style="padding:1.25rem 1.35rem;">
  <h2 style="margin:0 0 0.75rem;font-size:1.05rem;">好友与最近对话</h2>
  <?php if (empty($friends)) : ?>
    <p class="muted" style="margin:0;">暂无好友。搜索手机号或访问用户主页发送好友申请。</p>
  <?php else : ?>
    <ul style="list-style:none;padding:0;margin:0;">
      <?php foreach ($friends as $fr) : ?>
        <?php
          $fid = (int) $fr['id'];
          $lm = $fr['last_message'];
          $unreadN = (int) ($fr['unread_count'] ?? 0);
        ?>
        <li style="border-bottom:1px solid var(--border-subtle, rgba(255,255,255,0.08));padding:0.75rem 0;">
          <a href="<?= h(url('/chat/with/' . $fid)) ?>" style="text-decoration:none;color:inherit;display:block;">
            <strong><?= h((string) $fr['nickname']) ?></strong>
            <?php if ($unreadN > 0) : ?>
              <span class="nav-inbox-badge" style="margin-left:0.35rem;vertical-align:middle;"><?= $unreadN > 99 ? '99+' : $unreadN ?></span>
            <?php endif; ?>
            <?php if ($lm) : ?>
              <?php
                $preview = (string) $lm['body'];
                $fromMe = (int) $lm['from_user_id'] === (int) $u['id'];
              ?>
              <p class="muted" style="margin:0.35rem 0 0;font-size:0.88rem;">
                <?= $fromMe ? '我：' : '' ?><?= h(mb_substr($preview, 0, 80)) ?><?= mb_strlen($preview) > 80 ? '…' : '' ?>
              </p>
              <span class="muted" style="font-size:0.8rem;"><?= h((string) $lm['created_at']) ?></span>
            <?php else : ?>
              <p class="muted" style="margin:0.35rem 0 0;font-size:0.88rem;">暂无消息，点此开始聊天</p>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php endif; ?>
