<?php declare(strict_types=1); ?>
<div class="form-panel">
  <h1 style="margin-top:0;font-size:1.25rem;">重置密码</h1>
  <p class="muted" style="margin-top:0;">短信已发送至 <strong><?= h($masked) ?></strong>，请输入验证码并设置新密码。</p>
  <form method="post" action="<?= h(url('/forgot-password/reset')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="fp_code">短信验证码</label>
      <input id="fp_code" name="sms_code" type="text" required inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{4,6}" placeholder="4–6 位数字">
    </div>
    <div class="field">
      <label for="fp_pw">新密码</label>
      <input id="fp_pw" name="password" type="password" required minlength="6" autocomplete="new-password" placeholder="至少 6 位">
    </div>
    <div class="field">
      <label for="fp_pw2">确认新密码</label>
      <input id="fp_pw2" name="password_confirm" type="password" required minlength="6" autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;">重置密码</button>
  </form>
  <p class="muted" style="margin-top:1.25rem;margin-bottom:0;">
    <a href="<?= h(url('/forgot-password')) ?>">更换手机号重试</a>
    <span class="register-footer-dot">·</span>
    <a href="<?= h(url('/login')) ?>">返回登录</a>
  </p>
</div>
