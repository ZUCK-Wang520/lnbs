<?php declare(strict_types=1); ?>
<div class="form-panel">
  <h1 style="margin-top:0;font-size:1.25rem;">注册</h1>
  <p class="muted" style="margin-top:0;">加入校园讨论。</p>
  <form method="post" action="<?= h(url('/register')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="email">邮箱</label>
      <input id="email" name="email" type="email" required autocomplete="username">
    </div>
    <div class="field">
      <label for="nickname">昵称</label>
      <input id="nickname" name="nickname" type="text" required maxlength="64" autocomplete="nickname">
    </div>
    <div class="field">
      <label for="password">密码（至少 6 位）</label>
      <input id="password" name="password" type="password" required minlength="6" autocomplete="new-password">
    </div>
    <div class="field">
      <label for="password_confirm">确认密码</label>
      <input id="password_confirm" name="password_confirm" type="password" required minlength="6" autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;">注册</button>
  </form>
  <p class="muted" style="margin-top:1.25rem;margin-bottom:0;">
    已有账号？<a href="<?= h(url('/login')) ?>">登录</a>
  </p>
</div>
