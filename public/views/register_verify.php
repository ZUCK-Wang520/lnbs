<?php declare(strict_types=1); ?>
<div class="auth-page auth-page--register">
  <div class="auth-page-bg" aria-hidden="true"></div>
  <div class="form-panel register-card register-card--wide">
    <header class="register-card-head">
      <div class="register-orb register-orb--secondary" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0110 0v4"/>
        </svg>
      </div>
      <h1 class="register-title">完善资料</h1>
      <p class="register-lead muted">短信已发送至 <span class="register-phone-highlight"><?= h($masked) ?></span>，请输入验证码并设置昵称与密码（使用手机号登录）。</p>
    </header>

    <ol class="register-steps" aria-label="注册步骤">
      <li class="register-step is-done">
        <span class="register-step-dot"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span>
        <span class="register-step-label">验证手机</span>
      </li>
      <li class="register-step-connector is-active" aria-hidden="true"></li>
      <li class="register-step is-active">
        <span class="register-step-dot">2</span>
        <span class="register-step-label">完善资料</span>
      </li>
    </ol>

    <form method="post" action="<?= h(url('/register/complete')) ?>" class="register-verify-form">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

      <div class="register-form-section">
        <h2 class="register-form-section-title">短信验证</h2>
        <div class="field">
          <label for="sms_code">验证码</label>
          <input id="sms_code" class="register-code-input" name="sms_code" type="text" required inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{4,6}" placeholder="● ● ● ● ● ●">
        </div>
      </div>

      <div class="register-form-section">
        <h2 class="register-form-section-title">账号信息</h2>
        <div class="field">
          <label for="nickname">昵称</label>
          <input id="nickname" name="nickname" type="text" required maxlength="64" autocomplete="nickname" placeholder="在校园里的称呼">
        </div>
        <div class="field">
          <label for="password">密码</label>
          <input id="password" name="password" type="password" required minlength="6" autocomplete="new-password" placeholder="至少 6 位">
        </div>
        <div class="field">
          <label for="password_confirm">确认密码</label>
          <input id="password_confirm" name="password_confirm" type="password" required minlength="6" autocomplete="new-password">
        </div>
        <div class="field home-checkbox-field" style="margin-top:0.75rem;">
          <label class="home-checkbox">
            <input type="checkbox" name="agree_user_notice" value="1" required>
            <span>我已阅读并同意 <a href="<?= h(url('/user-notice')) ?>" target="_blank" rel="noopener noreferrer">《用户须知》</a></span>
          </label>
        </div>
        <div class="field home-checkbox-field" style="margin-top:0.35rem;">
          <label class="home-checkbox">
            <input type="checkbox" name="agree_privacy_policy" value="1" required>
            <span>我已阅读并同意 <a href="<?= h(url('/privacy-policy')) ?>" target="_blank" rel="noopener noreferrer">《隐私政策》</a></span>
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-register-submit">
        <span>完成注册</span>
        <svg class="btn-register-arrow" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </form>

    <p class="register-footer-link muted">
      <a href="<?= h(url('/register')) ?>">更换手机号</a>
      <span class="register-footer-dot">·</span>
      <a href="<?= h(url('/login')) ?>">返回登录</a>
    </p>
  </div>
</div>
