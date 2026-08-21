<?php declare(strict_types=1);
/** @var array<string,mixed> $peer */
/** @var list<array<string,mixed>> $messages */
/** @var int $me */
$pid = (int) $peer['id'];
$pnick = (string) $peer['nickname'];
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <a href="<?= h(url('/chat')) ?>">私信</a>
  <span> / </span>
  <span><?= h($pnick) ?></span>
</nav>

<div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem;margin-bottom:1rem;">
  <h1 style="margin:0;font-size:1.2rem;">与 <?= h($pnick) ?> 的对话</h1>
  <a class="btn btn-ghost btn-sm" href="<?= h(url('/user/' . $pid . '/topics')) ?>">对方主页</a>
</div>

<div class="card chat-thread" style="padding:1rem 1.15rem;margin-bottom:1rem;max-height:min(60vh,28rem);overflow-y:auto;">
  <?php if (empty($messages)) : ?>
    <p class="muted" style="margin:0;">还没有消息，打个招呼吧。</p>
  <?php else : ?>
    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.65rem;">
      <?php foreach ($messages as $m) : ?>
        <?php
          $fromMe = (int) $m['from_user_id'] === $me;
          $bg = $fromMe ? 'var(--accent-soft, rgba(99,102,241,0.2))' : 'var(--surface-2, rgba(255,255,255,0.06))';
        ?>
        <li style="display:flex;justify-content:<?= $fromMe ? 'flex-end' : 'flex-start' ?>;">
          <div style="max-width:85%;padding:0.5rem 0.75rem;border-radius:0.65rem;background:<?= h($bg) ?>;">
            <p style="margin:0;white-space:pre-wrap;word-break:break-word;"><?= h((string) $m['body']) ?></p>
            <span class="muted" style="font-size:0.75rem;display:block;margin-top:0.35rem;"><?= h((string) $m['created_at']) ?></span>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<form method="post" action="<?= h(url('/chat/send')) ?>" class="card js-moderation-submit" style="padding:1rem 1.15rem;">
  <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="to_user_id" value="<?= (int) $pid ?>">
  <label for="chat-body" class="muted" style="display:block;margin-bottom:0.35rem;">消息</label>
  <textarea id="chat-body" name="body" rows="3" class="input" style="width:100%;resize:vertical;" maxlength="2000" required placeholder="输入消息，最多 2000 字"></textarea>
  <div style="margin-top:0.75rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
    <button type="submit" class="btn btn-primary btn-sm">发送</button>
    <a href="<?= h(url('/chat')) ?>" class="btn btn-ghost btn-sm">返回私信中心</a>
  </div>
</form>
