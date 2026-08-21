<?php declare(strict_types=1);
$coupleSubnavTitle = '关于我们';
require __DIR__ . '/_subnav.php';
$mp = is_array($mePublic ?? null) ? $mePublic : ['nickname' => '', 'avatar' => null];
$pp = is_array($partner ?? null) ? $partner : null;
$mi = mb_substr((string) ($mp['nickname'] ?? ''), 0, 1, 'UTF-8');
$pi = $pp ? mb_substr((string) $pp['nickname'], 0, 1, 'UTF-8') : '?';
?>
<div class="couple-lg-about card">
  <h1 class="couple-lg-page-title">关于我们</h1>
  <div class="couple-lg-about-hero">
    <div class="couple-lg-glass-avatar">
      <?php if (!empty($meAv)) : ?>
        <img src="<?= h($meAv) ?>" alt="" width="96" height="96" loading="lazy">
      <?php else : ?>
        <span class="couple-lg-glass-fallback"><?= h($mi) ?></span>
      <?php endif; ?>
    </div>
    <span class="couple-lg-about-heart" aria-hidden="true">♥</span>
    <div class="couple-lg-glass-avatar">
      <?php if ($pp && !empty($partnerAv)) : ?>
        <img src="<?= h($partnerAv) ?>" alt="" width="96" height="96" loading="lazy">
      <?php else : ?>
        <span class="couple-lg-glass-fallback"><?= h($pi) ?></span>
      <?php endif; ?>
    </div>
  </div>
  <p class="couple-lg-about-names">
    <?= h((string) ($mp['nickname'] ?? '')) ?>
    <?php if ($pp) : ?>
      <span class="muted">&</span> <?= h((string) $pp['nickname']) ?>
    <?php endif; ?>
  </p>
  <p class="couple-lg-about-days">已相伴 <strong><?= (int) ($days ?? 0) ?></strong> 天</p>
  <p class="muted" style="font-size:0.88rem;">绑定于 <?= h((string) ($coupleRow['bound_at'] ?? '')) ?></p>
  <div class="couple-lg-about-actions">
    <?php if ($pp) : ?>
      <a class="btn btn-primary btn-sm" href="<?= h(url('/chat/with/' . (int) $pp['id'])) ?>">发私信</a>
      <a class="btn btn-ghost btn-sm" href="<?= h(url('/user/' . (int) $pp['id'] . '/topics')) ?>">Ta 的主题</a>
    <?php endif; ?>
    <a class="btn btn-ghost btn-sm" href="<?= h(url('/profile')) ?>">我的资料</a>
  </div>
</div>
