<?php declare(strict_types=1);
$active = (int) $box['is_active'] === 1;
$loggedIn = $current !== null;
?>
<section class="ask-public-hero card">
  <div class="ask-public-glow" aria-hidden="true"></div>
  <span class="ask-public-avatar" aria-hidden="true"><?= h(mb_substr((string) $box['owner_nickname'], 0, 1)) ?></span>
  <span class="ask-public-kicker">匿名提问箱</span>
  <h1 class="ask-public-title"><?= h((string) $box['title']) ?></h1>
  <p class="ask-public-owner">向 <strong><?= h((string) $box['owner_nickname']) ?></strong> 提问</p>
  <?php if (trim((string) ($box['intro'] ?? '')) !== '') : ?>
    <p class="ask-public-intro"><?= h((string) $box['intro']) ?></p>
  <?php endif; ?>
  <p class="ask-public-safe">🔒 完全匿名 · 对方看不到你是谁</p>
</section>

<?php if ($isOwner) : ?>
  <div class="form-panel ask-public-form">
    <p class="muted" style="margin:0;">这是你自己的提问箱。快去 <a href="<?= h(url('/ask/box/' . (int) $box['id'])) ?>">查看收到的提问</a>，或 <a href="<?= h(url('/ask/box/' . (int) $box['id'] . '/poster')) ?>">分享二维码海报</a>。</p>
  </div>
<?php elseif (!$active) : ?>
  <div class="form-panel ask-public-form">
    <p class="muted" style="margin:0;">该提问箱已暂停接收新提问，感谢关注～</p>
  </div>
<?php elseif (!$loggedIn) : ?>
  <div class="form-panel ask-public-form ask-public-login">
    <p style="margin:0 0 0.8rem;">提问需要先登录账户（仅用于防止骚扰，<strong>对方依然看不到你的身份</strong>）。</p>
    <a class="btn btn-primary" href="<?= h(url('/login')) ?>">登录后提问</a>
    <a class="btn btn-ghost" href="<?= h(url('/register')) ?>">注册新账号</a>
  </div>
<?php else : ?>
  <div class="form-panel ask-public-form">
    <form method="post" action="<?= h(url('/ask/submit')) ?>" class="js-moderation-submit">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="token" value="<?= h((string) $box['token']) ?>">
      <div class="field">
        <label for="ask_content">你的问题</label>
        <textarea id="ask_content" name="content" required maxlength="800" rows="5" placeholder="写下你想匿名问 Ta 的话…（1–800 字）"></textarea>
      </div>
      <button type="submit" class="btn btn-primary">匿名发送</button>
      <p class="muted" style="margin:0.7rem 0 0;font-size:0.82rem;">发送后，<?= h((string) $box['owner_nickname']) ?> 只会看到问题内容，不会知道是谁提问。</p>
    </form>
  </div>
<?php endif; ?>

<?php if (!empty($publicQa)) : ?>
  <h2 class="ask-section-title" style="margin-top:1.8rem;">公开问答</h2>
  <ul class="ask-wall-list">
    <?php foreach ($publicQa as $qa) : ?>
      <li class="ask-wall-card card">
        <div class="ask-wall-q">
          <span class="ask-wall-q-mark" aria-hidden="true">Q</span>
          <p><?= anon_ask_text_html((string) $qa['content']) ?></p>
        </div>
        <div class="ask-wall-a">
          <span class="ask-wall-a-mark" aria-hidden="true">A</span>
          <p><?= anon_ask_text_html((string) $qa['answer']) ?></p>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
