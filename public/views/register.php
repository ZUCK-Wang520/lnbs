<?php declare(strict_types=1);
$hasPending = !empty($pending) && !empty($pending['expires_at']) && (int) $pending['expires_at'] > time();
?>
<div class="auth-page auth-page--register">
  <div class="auth-page-bg" aria-hidden="true"></div>
  <div class="form-panel register-card">
    <header class="register-card-head">
      <div class="register-orb" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
        </svg>
      </div>
      <h1 class="register-title">创建账号</h1>
      <p class="register-lead muted">使用手机验证码完成注册，防刷保护已启用。</p>
    </header>

    <ol class="register-steps" aria-label="注册步骤">
      <li class="register-step is-active">
        <span class="register-step-dot">1</span>
        <span class="register-step-label">验证手机</span>
      </li>
      <li class="register-step-connector" aria-hidden="true"></li>
      <li class="register-step">
        <span class="register-step-dot">2</span>
        <span class="register-step-label">完善资料</span>
      </li>
    </ol>

    <?php if ($hasPending) : ?>
      <div class="register-pending-banner">
        <div class="register-pending-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <p class="register-pending-text">验证码已发送至 <strong><?= h($masked) ?></strong>，10 分钟内有效。</p>
        <div class="register-pending-actions">
          <a class="btn btn-primary btn-sm" href="<?= h(url('/register/welcome')) ?>">欢迎动效</a>
          <a class="btn btn-ghost btn-sm" href="<?= h(url('/register/verify')) ?>">填写验证码</a>
        </div>
      </div>
    <?php endif; ?>

    <form id="register-sms-form" method="post" action="<?= h(url('/register/send-sms')) ?>" class="register-sms-form">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <?php $geetest_form_id = 'register-sms-form'; require VIEWS . '/partials/geetest_bind.php'; ?>
      <div class="field field-register-phone">
        <label for="reg_phone">手机号码</label>
        <div class="register-input-wrap">
          <span class="register-input-prefix" aria-hidden="true">+86</span>
          <input id="reg_phone" name="phone" type="tel" required inputmode="numeric" autocomplete="tel" maxlength="11" placeholder="请输入 11 位手机号">
        </div>
      </div>
      <div class="field home-checkbox-field" style="margin-top:0.5rem;">
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
      <button type="submit" class="btn btn-primary btn-register-submit">
        <span>发送验证码</span>
        <svg class="btn-register-arrow" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </form>

    <ul class="register-trust-row muted" aria-label="说明">
      <li>短信由官方通道发送</li>
      <li>号码仅用于注册验证</li>
    </ul>

    <p class="register-footer-link muted">
      已有账号？<a href="<?= h(url('/login')) ?>">登录</a>
    </p>
  </div>
</div>
