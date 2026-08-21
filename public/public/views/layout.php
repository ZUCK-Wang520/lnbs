<?php
declare(strict_types=1);
$appName = $GLOBALS['APP_CONFIG']['app']['name'] ?? '论坛';
$u = auth_user();
$flash = flash_get();
$isAdminSection = $isAdminSection ?? false;
$onlineCount = online_count();
$layout_minimal = !empty($layout_minimal ?? false);
$siteLogoUrl = site_logo_url();
$confessUnread = $u ? confession_unread_count((int) $u['id']) : 0;
$chatPending = ($u && chat_tables_ok()) ? chat_count_incoming_pending((int) $u['id']) : 0;
$messagesUnread = $u ? topic_reply_notifications_unread_count((int) $u['id']) : 0;
$__p = request_path();
$confessWriteActive = $__p === '/confessions/new' || $__p === '/confessions/sent';
$confessInboxActive = $__p === '/confessions' || (bool) preg_match('#^/confession/\d+$#', $__p);
$chatNavActive = $__p === '/chat' || (bool) preg_match('#^/chat/with/\d+$#', $__p);
$messagesNavActive = $__p === '/messages';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <script>
(function(){try{var k='luba-theme',t=localStorage.getItem(k);if(t!=='light'&&t!=='dark')t='dark';document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();
  </script>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="dark light">
  <title><?= h($pageTitle) ?> · <?= h($appName) ?></title>
  <style>
<?php
$__styleFile = dirname(__DIR__) . '/assets/theme.css';
if (is_readable($__styleFile)) {
    readfile($__styleFile);
} else {
    echo '/* 缺少 public/assets/theme.css，请上传该文件 */';
}
?>
  </style>
</head>
<body<?= $layout_minimal ? ' class="is-layout-minimal"' : '' ?>>
  <header class="topbar">
    <div class="topbar-inner">
      <a href="<?= h(url('/')) ?>" class="brand">
        <?php if ($siteLogoUrl) : ?>
          <img class="brand-logo" src="<?= h($siteLogoUrl) ?>" alt="<?= h($appName) ?>" width="40" height="40" decoding="async" fetchpriority="high">
        <?php else : ?>
          <span class="brand-mark" aria-hidden="true"></span>
        <?php endif; ?>
        <span class="brand-text"><?= h($appName) ?></span>
      </a>
      <?php if ($layout_minimal) : ?>
      <nav class="nav-links" aria-label="主导航">
        <button type="button" class="theme-toggle" id="themeToggle" aria-label="切换日间或夜间模式" title="日间 / 夜间">
          <span class="theme-toggle-track">
            <span class="theme-toggle-thumb">
              <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
              <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </span>
          </span>
        </button>
        <a href="<?= h(url('/register')) ?>" class="btn btn-ghost btn-sm">返回注册</a>
      </nav>
      <?php else : ?>
      <nav class="nav-links" aria-label="主导航">
        <button type="button" class="theme-toggle" id="themeToggle" aria-label="切换日间或夜间模式" title="日间 / 夜间">
          <span class="theme-toggle-track">
            <span class="theme-toggle-thumb">
              <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
              <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </span>
          </span>
        </button>
        <div class="online-pill" role="status" title="最近 5 分钟内有页面访问的访客（按浏览器会话统计）">
          <span class="online-pill-dot" aria-hidden="true"></span>
          <span class="online-pill-num"><?= (int) $onlineCount ?></span>
          <span class="online-pill-label">在线</span>
        </div>
        <a href="<?= h(url('/')) ?>" class="<?= !$isAdminSection && request_path() === '/' ? 'is-active' : '' ?>">首页</a>
        <?php if ($u) : ?>
          <a href="<?= h(url('/confessions/new')) ?>" class="nav-confess-link <?= $confessWriteActive ? 'is-active' : '' ?>">表白</a>
          <a href="<?= h(url('/confessions')) ?>" class="nav-inbox-link <?= $confessInboxActive ? 'is-active' : '' ?>" title="表白收件箱">
            收件箱<?php if ($confessUnread > 0) : ?><span class="nav-inbox-badge"><?= (int) $confessUnread ?></span><?php endif; ?>
          </a>
          <a href="<?= h(url('/chat')) ?>" class="nav-inbox-link <?= $chatNavActive ? 'is-active' : '' ?>" title="私信与好友">
            私信<?php if ($chatPending > 0) : ?><span class="nav-inbox-badge"><?= (int) $chatPending ?></span><?php endif; ?>
          </a>
          <a href="<?= h(url('/messages')) ?>" class="nav-inbox-link <?= $messagesNavActive ? 'is-active' : '' ?>" title="主题回复通知">
            消息<?php if ($messagesUnread > 0) : ?><span class="nav-inbox-badge"><?= (int) $messagesUnread ?></span><?php endif; ?>
          </a>
          <a href="<?= h(url('/profile')) ?>" class="nav-profile-link <?= request_path() === '/profile' ? 'is-active' : '' ?>"><?= h($u['nickname']) ?></a>
          <?php if (($u['role'] ?? '') === 'admin') : ?>
            <a href="<?= h(url('/admin')) ?>" class="<?= $isAdminSection ? 'is-active' : '' ?>">后台</a>
          <?php endif; ?>
          <form class="inline-form" method="post" action="<?= h(url('/logout')) ?>">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <button type="submit" class="btn btn-ghost btn-sm">退出</button>
          </form>
        <?php else : ?>
          <a href="<?= h(url('/login')) ?>" class="<?= request_path() === '/login' ? 'is-active' : '' ?>">登录</a>
          <a href="<?= h(url('/register')) ?>" class="<?= (request_path() === '/register' || str_starts_with(request_path(), '/register/')) ? 'is-active' : '' ?>">注册</a>
        <?php endif; ?>
      </nav>
      <?php endif; ?>
    </div>
  </header>
  <div class="shell">
    <main>
      <?php if ($flash) : ?>
        <div class="flash flash-<?= h($flash['type'] === 'success' ? 'success' : 'error') ?>">
          <?= h($flash['message']) ?>
        </div>
      <?php endif; ?>
      <?php require VIEWS . '/' . $__view; ?>
    </main>
    <?php if (!$layout_minimal) : ?>
    <footer class="site-footer">
      <div class="site-footer-inner">
        <span class="site-footer-online">
          <span class="online-pill-dot online-pill-dot--footer" aria-hidden="true"></span>
          约 <strong><?= (int) $onlineCount ?></strong> 人在线
        </span>
        <span class="site-footer-divider" aria-hidden="true"></span>
        <span><?= h($appName) ?> · 文明发言，友善交流</span>
      </div>
    </footer>
    <?php endif; ?>
  </div>
  <script>
(function(){var k='luba-theme',root=document.documentElement,btn=document.getElementById('themeToggle');if(!btn)return;function sync(){var t=root.getAttribute('data-theme')||'dark';btn.setAttribute('aria-pressed',t==='light');}sync();btn.addEventListener('click',function(){var cur=root.getAttribute('data-theme')||'dark',next=cur==='light'?'dark':'light';btn.classList.add('theme-toggle-pulse');setTimeout(function(){btn.classList.remove('theme-toggle-pulse');},480);root.setAttribute('data-theme',next);try{localStorage.setItem(k,next);}catch(e){}document.body.classList.add('theme-switch-anim');clearTimeout(window._lubaTh);window._lubaTh=setTimeout(function(){document.body.classList.remove('theme-switch-anim');},620);sync();});})();
(function(){var msg='正在检测言论是否违规，请稍候…';document.querySelectorAll('form.js-moderation-submit').forEach(function(form){form.addEventListener('submit',function(){if(form.getAttribute('data-moderation-sent')==='1')return;form.setAttribute('data-moderation-sent','1');form.querySelectorAll('button[type="submit"]').forEach(function(btn){btn.disabled=true;btn.textContent=msg;});});});})();
  </script>
</body>
</html>
