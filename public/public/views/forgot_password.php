<?php declare(strict_types=1); ?>
<div class="form-panel">
  <h1 style="margin-top:0;font-size:1.25rem;">忘记密码</h1>
  <p class="muted" style="margin-top:0;">仅支持已绑定<strong>手机号</strong>的账号；将向该号码发送 6 位验证码（Spug 要求 4–6 位）。同一手机号与 IP 有发送频率与每日上限，请勿频繁点击。</p>
  <form method="post" action="<?= h(url('/forgot-password/send-sms')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="fp_phone">注册手机号</label>
      <input id="fp_phone" name="phone" type="tel" required inputmode="numeric" autocomplete="tel" maxlength="11" placeholder="11 位手机号">
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;">获取验证码</button>
  </form>
  <p class="muted" style="margin-top:1.25rem;margin-bottom:0;">
    <a href="<?= h(url('/login')) ?>">返回登录</a>
    <span class="register-footer-dot">·</span>
    <a href="<?= h(url('/register')) ?>">注册账号</a>
  </p>
</div>
