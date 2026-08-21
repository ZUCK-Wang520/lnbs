<?php declare(strict_types=1); ?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>匿名提问箱</span>
</nav>

<section class="ask-hero">
  <div class="ask-hero-glow" aria-hidden="true"></div>
  <div class="ask-hero-body">
    <span class="ask-hero-kicker">匿名提问箱</span>
    <h1 class="ask-hero-title">创建你的提问箱，收获真心话</h1>
    <p class="ask-hero-sub">生成专属二维码海报，任何人扫码登录即可向你匿名提问。<strong>你只会看到问题，永远看不到是谁问的</strong>，可以逐条回复，还能把精彩问答公开到问答墙。</p>
  </div>
</section>

<div class="form-panel ask-create-panel">
  <h2 class="ask-section-title">新建提问箱</h2>
  <form method="post" action="<?= h(url('/ask/create')) ?>" class="js-moderation-submit">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="ask_title">标题</label>
      <input id="ask_title" name="title" type="text" required maxlength="60" placeholder="例如：有什么想问我的？">
    </div>
    <div class="field">
      <label for="ask_intro">一句话介绍（选填）</label>
      <input id="ask_intro" name="intro" type="text" maxlength="300" placeholder="悄悄话我都会认真看，放心提问～">
    </div>
    <button type="submit" class="btn btn-primary">创建提问箱</button>
  </form>
</div>

<h2 class="ask-section-title" style="margin-top:1.6rem;">我的提问箱</h2>
<?php if (empty($boxes)) : ?>
  <p class="muted">还没有提问箱，创建一个开始收集匿名提问吧。</p>
<?php else : ?>
  <ul class="ask-box-grid">
    <?php foreach ($boxes as $b) : ?>
      <?php
        $total = (int) $b['q_total'];
        $unread = (int) $b['q_unread'];
        $pending = (int) $b['q_pending'];
        $active = (int) $b['is_active'] === 1;
      ?>
      <li class="ask-box-card card">
        <div class="ask-box-card-top">
          <a class="ask-box-card-title" href="<?= h(url('/ask/box/' . (int) $b['id'])) ?>"><?= h((string) $b['title']) ?></a>
          <span class="ask-status-pill <?= $active ? 'is-on' : 'is-off' ?>"><?= $active ? '接收中' : '已暂停' ?></span>
        </div>
        <?php if (trim((string) ($b['intro'] ?? '')) !== '') : ?>
          <p class="ask-box-card-intro muted"><?= h((string) $b['intro']) ?></p>
        <?php endif; ?>
        <div class="ask-box-card-stats">
          <span><strong><?= $total ?></strong> 提问</span>
          <span class="<?= $pending > 0 ? 'ask-stat-hot' : '' ?>"><strong><?= $pending ?></strong> 待回复</span>
          <?php if ($unread > 0) : ?><span class="ask-stat-badge"><?= $unread ?> 未读</span><?php endif; ?>
        </div>
        <div class="ask-box-card-actions">
          <a class="btn btn-primary btn-sm" href="<?= h(url('/ask/box/' . (int) $b['id'])) ?>">查看提问</a>
          <a class="btn btn-ghost btn-sm" href="<?= h(url('/ask/box/' . (int) $b['id'] . '/poster')) ?>">分享海报</a>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
