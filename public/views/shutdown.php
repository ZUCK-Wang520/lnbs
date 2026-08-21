<?php declare(strict_types=1);
$appName = (string) ($GLOBALS['APP_CONFIG']['app']['name'] ?? '论坛');
$msg = trim((string) ($shutdownMessage ?? ''));
$eta = trim((string) ($shutdownEta ?? ''));
if ($msg === '') {
    $msg = '站点维护中，请稍后再试。';
}
?>
<section class="shutdown-page" aria-labelledby="shutdown-title">
  <div class="shutdown-page-bg" aria-hidden="true"></div>
  <div class="shutdown-page-inner">
    <div class="shutdown-status" role="status">
      <span class="shutdown-status-code">503</span>
      <span class="shutdown-status-label">服务暂不可用</span>
    </div>

    <div class="shutdown-icon-wrap" aria-hidden="true">
      <svg class="shutdown-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="2" opacity="0.35"/>
        <path d="M32 18v16l10 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M48 14l3 5M16 50l-3 5M52 44l5 3M12 20l-5 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.5"/>
      </svg>
    </div>

    <p class="shutdown-kicker"><?= h($appName) ?></p>
    <h1 id="shutdown-title" class="shutdown-title">站点维护中</h1>
    <p class="shutdown-lead muted">
      我们正在升级或排查问题，暂时无法访问论坛功能。感谢你的耐心与理解。
    </p>

    <div class="shutdown-message card">
      <p class="shutdown-message-label">公告</p>
      <div class="shutdown-message-body"><?= nl2br(h($msg), false) ?></div>
    </div>

    <?php if ($eta !== '') : ?>
      <p class="shutdown-eta">
        <span class="shutdown-eta-label muted">预计恢复</span>
        <strong><?= h($eta) ?></strong>
      </p>
    <?php endif; ?>

    <div class="shutdown-actions">
      <button type="button" class="btn btn-primary" onclick="location.reload()">刷新页面</button>
      <a class="btn btn-ghost" href="<?= h(url('/login')) ?>">管理员登录</a>
    </div>

    <p class="shutdown-foot muted">站点管理员或已开通后台权限的账号，请使用「管理员登录」重新进入；登录后可正常浏览全站与后台。</p>
  </div>
</section>
