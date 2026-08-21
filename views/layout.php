<?php
declare(strict_types=1);
$appName = $GLOBALS['APP_CONFIG']['app']['name'] ?? '论坛';
$u = auth_user();
$flash = flash_get();
$isAdminSection = $isAdminSection ?? false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?> · <?= h($appName) ?></title>
  <style>
<?php
$__styleFile = dirname(__DIR__) . '/public/assets/theme.css';
if (is_readable($__styleFile)) {
    readfile($__styleFile);
} else {
    echo '/* 缺少 public/assets/theme.css，请上传该文件 */';
}
?>
  </style>
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <a href="<?= h(url('/')) ?>" class="brand">
        <span class="brand-mark" aria-hidden="true"></span>
        <span><?= h($appName) ?></span>
      </a>
      <nav class="nav-links" aria-label="主导航">
        <a href="<?= h(url('/')) ?>" class="<?= !$isAdminSection && request_path() === '/' ? 'is-active' : '' ?>">首页</a>
        <?php if ($u) : ?>
          <span class="muted"><?= h($u['nickname']) ?></span>
          <?php if (($u['role'] ?? '') === 'admin') : ?>
            <a href="<?= h(url('/admin')) ?>" class="<?= $isAdminSection ? 'is-active' : '' ?>">后台</a>
          <?php endif; ?>
          <form class="inline-form" method="post" action="<?= h(url('/logout')) ?>">
            <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
            <button type="submit" class="btn btn-ghost btn-sm">退出</button>
          </form>
        <?php else : ?>
          <a href="<?= h(url('/login')) ?>" class="<?= request_path() === '/login' ? 'is-active' : '' ?>">登录</a>
          <a href="<?= h(url('/register')) ?>" class="<?= request_path() === '/register' ? 'is-active' : '' ?>">注册</a>
        <?php endif; ?>
      </nav>
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
    <footer class="site-footer">
      <?= h($appName) ?> · 文明发言，友善交流
    </footer>
  </div>
</body>
</html>
