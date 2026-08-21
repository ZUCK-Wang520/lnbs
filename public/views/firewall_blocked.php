<?php declare(strict_types=1);
$appName = (string) ($GLOBALS['APP_CONFIG']['app']['name'] ?? '鲁巴校园论坛');
$detail = trim((string) ($firewallDetail ?? ''));
?>
<section class="shutdown-page firewall-blocked-page" aria-labelledby="firewall-title">
  <div class="shutdown-page-bg" aria-hidden="true"></div>
  <div class="shutdown-page-inner">
    <div class="shutdown-status" role="status">
      <span class="shutdown-status-code">403</span>
      <span class="shutdown-status-label">访问已拦截</span>
    </div>

    <div class="shutdown-icon-wrap" aria-hidden="true">
      <svg class="shutdown-icon" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M32 8L54 18v14c0 14-9.5 27-22 30C19.5 59 10 46 10 32V18L32 8z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
        <path d="M24 32l6 6 12-14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>

    <p class="shutdown-kicker"><?= h($appName) ?></p>
    <h1 id="firewall-title" class="shutdown-title">安全防护已启用</h1>
    <p class="shutdown-lead muted">
      系统检测到来自您当前网络的异常或恶意请求（如注入攻击、扫描探测等），已自动限制访问。
    </p>

    <div class="shutdown-message card">
      <p class="shutdown-message-label">说明</p>
      <div class="shutdown-message-body">
        <p>安全防护由<strong><?= h($appName) ?></strong>提供。</p>
        <?php if ($detail !== '') : ?>
          <p class="firewall-blocked-detail muted">触发原因：<?= h($detail) ?></p>
        <?php endif; ?>
        <p class="muted">若您认为属于误判，请联系站点管理员申诉并说明访问时间与网络环境。</p>
      </div>
    </div>

    <p class="shutdown-foot muted">您的 IP 已被自动加入封禁列表；管理员可在后台「用户管理」中解除对应 IP 的防火墙封禁。</p>
  </div>
</section>
