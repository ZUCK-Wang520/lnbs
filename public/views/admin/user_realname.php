<?php declare(strict_types=1);
$user = is_array($user ?? null) ? $user : null;
$name = (string) ($name ?? '');
$idcard = (string) ($idcard ?? '');
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/admin')) ?>">后台</a>
  <span> / </span>
  <a href="<?= h(url('/admin/users')) ?>">用户</a>
  <span> / </span>
  <span>实名信息</span>
</nav>
<h1 style="margin-bottom:0.75rem;">实名信息</h1>
<div class="card" style="max-width:720px;padding:1.05rem 1.15rem;">
  <?php if (!$user) : ?>
    <p class="muted" style="margin:0;">用户不存在。</p>
  <?php else : ?>
    <p class="muted" style="margin-top:0;">用户：<strong><?= h((string) ($user['nickname'] ?? '')) ?></strong>（ID <?= (int) ($user['id'] ?? 0) ?>）</p>
    <?php if ((int) ($user['realname_verified'] ?? 0) !== 1) : ?>
      <p class="muted">该用户未标记为已实名。</p>
    <?php endif; ?>
    <?php if (!empty($user['deleted_at'])) : ?>
      <p class="muted">状态：已注销（<?= h((string) $user['deleted_at']) ?>）</p>
    <?php endif; ?>
    <hr style="border:none;border-top:1px solid rgba(255,255,255,.10);margin:0.9rem 0;">
    <dl class="profile-dl" style="margin:0;">
      <div class="profile-dl-row">
        <dt>实名姓名</dt>
        <dd><?= $name !== '' ? h($name) : '<span class="muted">未存储或无法解密</span>' ?></dd>
      </div>
      <div class="profile-dl-row">
        <dt>身份证号</dt>
        <dd><?= $idcard !== '' ? h($idcard) : '<span class="muted">未存储或无法解密</span>' ?></dd>
      </div>
      <div class="profile-dl-row">
        <dt>认证时间</dt>
        <dd><?= !empty($user['realname_verified_at']) ? h((string) $user['realname_verified_at']) : '—' ?></dd>
      </div>
    </dl>
    <p class="muted" style="margin:0.9rem 0 0;font-size:0.88rem;">提示：若显示“无法解密”，请确认 `config.local.php` 的 `realname.storage_key` 与入库时一致，并已执行 `migration_user_realname_identity.sql`。</p>
  <?php endif; ?>
</div>

