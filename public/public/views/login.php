<?php declare(strict_types=1);
$rememberDays = (int) (($GLOBALS['APP_CONFIG']['auth']['remember_days'] ?? 30));
if ($rememberDays < 1) {
    $rememberDays = 30;
}
?>
<div class="form-panel">
  <h1 style="margin-top:0;font-size:1.25rem;">登录</h1>
  <p class="muted" style="margin-top:0;">新用户请使用<strong>注册手机号</strong>登录；仅邮箱的老账号仍可填邮箱。</p>
  <form method="post" action="<?= h(url('/login')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="account">手机号或邮箱</label>
      <input id="account" name="account" type="text" required autocomplete="username" placeholder="11 位手机号，或老账号邮箱">
    </div>
    <div class="field">
      <label for="password">密码</label>
      <input id="password" name="password" type="password" required autocomplete="current-password">
    </div>
    <div class="field home-checkbox-field" style="margin-bottom:1rem;">
      <label class="home-checkbox">
        <input type="checkbox" name="remember" value="1">
        <span>记住登录（本设备约 <?= (int) $rememberDays ?> 天内保持登录，请勿在公共电脑勾选）</span>
      </label>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;">登录</button>
  </form>
  <p class="muted" style="margin-top:1.25rem;margin-bottom:0;">
    <a href="<?= h(url('/forgot-password')) ?>">忘记密码</a>
    <span class="register-footer-dot">·</span>
    还没有账号？<a href="<?= h(url('/register')) ?>">注册</a>
  </p>
</div>
