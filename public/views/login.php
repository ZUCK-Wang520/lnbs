<?php declare(strict_types=1);
$rememberDays = (int) (($GLOBALS['APP_CONFIG']['auth']['remember_days'] ?? 30));
if ($rememberDays < 1) {
    $rememberDays = 30;
}
?>
<div class="form-panel">
  <h1 style="margin-top:0;font-size:1.25rem;">登录</h1>
  <p class="muted" style="margin-top:0;">新用户请使用<strong>注册手机号</strong>登录；仅邮箱的老账号仍可填邮箱。</p>
  <form id="login-form" method="post" action="<?= h(url('/login')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="account">手机号或邮箱</label>
      <input id="account" name="account" type="text" required autocomplete="username" placeholder="11 位手机号，或老账号邮箱">
    </div>
    <div class="field">
      <label for="password">密码</label>
      <input id="password" name="password" type="password" required autocomplete="current-password">
    </div>
    <input type="hidden" name="geetest_challenge" id="geetest_challenge" value="">
    <input type="hidden" name="geetest_validate" id="geetest_validate" value="">
    <input type="hidden" name="geetest_seccode" id="geetest_seccode" value="">
    <input type="hidden" id="password_rsa" name="password_rsa" value="">
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

<script src="https://static.geetest.com/static/js/gt.0.5.0.js"></script>
<script>
(() => {
  const form = document.getElementById('login-form');
  const passwordEl = document.getElementById('password');
  const passwordRsaEl = document.getElementById('password_rsa');
  const submitBtn = form ? form.querySelector('button[type="submit"],input[type="submit"]') : null;
  const gtChalEl = document.getElementById('geetest_challenge');
  const gtValEl = document.getElementById('geetest_validate');
  const gtSecEl = document.getElementById('geetest_seccode');
  if (!form || !passwordEl || !passwordRsaEl) return;

  function safeRequestSubmit() {
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
      return;
    }
    // fallback that still triggers submit handlers
    if (submitBtn && typeof submitBtn.click === 'function') {
      submitBtn.click();
      return;
    }
    // last resort (may bypass submit handlers)
    form.submit();
  }

  // ---- GeeTest init (page-load) ----
  const geetestRegisterUrl = <?= json_encode(url('/geetest/register'), JSON_UNESCAPED_SLASHES) ?>;
  let captchaObj = null;
  let geetestInitInFlight = false;
  let geetestInitOk = false;
  function initGeeTest() {
    if (geetestInitOk || geetestInitInFlight) return;
    if (typeof window.initGeetest !== 'function') return;
    geetestInitInFlight = true;
    const sep = geetestRegisterUrl.indexOf('?') >= 0 ? '&' : '?';
    fetch(geetestRegisterUrl + sep + 't=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
      .then(r => r.ok ? r.json() : null)
      .then(data => {
        geetestInitInFlight = false;
        if (!data || !data.gt || !data.challenge) return;
        window.initGeetest({
          gt: data.gt,
          challenge: data.challenge,
          offline: !data.success,
          new_captcha: true,
          // 需要配合 captchaObj.verify()：登录按钮点击后触发验证
          product: 'bind',
          https: true,
          api_server: 'api.geevisit.com'
        }, (obj) => {
          captchaObj = obj;
          geetestInitOk = true;
        });
      })
      .catch(() => { geetestInitInFlight = false; });
  }
  initGeeTest();
  let gtTry = 0;
  const gtPoll = setInterval(() => {
    gtTry++;
    initGeeTest();
    if (geetestInitOk || gtTry > 80) clearInterval(gtPoll); // ~12s
  }, 150);

  // ---- RSA meta fetch (optional) ----
  const hasWebCrypto = !!(window.crypto && window.crypto.subtle && window.TextEncoder);

  const metaUrl = <?= json_encode(url('/auth/rsa-meta'), JSON_UNESCAPED_SLASHES) ?>;
  let meta = null;
  let metaPromise = hasWebCrypto ? fetch(metaUrl, { credentials: 'same-origin' })
    .then(r => r.ok ? r.json() : null)
    .then(j => (j && j.ok) ? j : null)
    .catch(() => null) : Promise.resolve(null);

  function pemToArrayBuffer(pem) {
    const b64 = pem.replace(/-----(BEGIN|END) PUBLIC KEY-----/g, '').replace(/\s+/g, '');
    const bin = atob(b64);
    const bytes = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
    return bytes.buffer;
  }

  async function encryptPassword(plain, pubPem, nonce) {
    const spki = pemToArrayBuffer(pubPem);
    const key = await crypto.subtle.importKey(
      'spki',
      spki,
      { name: 'RSA-OAEP', hash: 'SHA-1' },
      false,
      ['encrypt']
    );
    const payload = JSON.stringify({ password: plain, nonce, ts: Math.floor(Date.now() / 1000) });
    const data = new TextEncoder().encode(payload);
    const ct = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, key, data);
    const bytes = new Uint8Array(ct);
    let bin = '';
    const chunk = 0x8000;
    for (let i = 0; i < bytes.length; i += chunk) {
      bin += String.fromCharCode.apply(null, bytes.subarray(i, i + chunk));
    }
    return btoa(bin);
  }

  form.addEventListener('submit', async (e) => {
    // 1) GeeTest gate: if backend enabled, it will require these fields.
    if (gtChalEl && gtValEl && gtSecEl) {
      if (!(gtChalEl.value && gtValEl.value && gtSecEl.value)) {
        if (captchaObj && typeof captchaObj.verify === 'function') {
          e.preventDefault();
          // avoid stacking multiple handlers on repeated clicks
          if (!captchaObj.__lnbsBound) {
            captchaObj.__lnbsBound = true;
            captchaObj.onSuccess(() => {
              const v = captchaObj.getValidate && captchaObj.getValidate();
              if (v) {
                gtChalEl.value = v.geetest_challenge || '';
                gtValEl.value = v.geetest_validate || '';
                gtSecEl.value = v.geetest_seccode || '';
              }
              safeRequestSubmit();
            });
          }
          captchaObj.verify();
          return;
        }
        // captcha 未初始化：阻止提交，避免用户反复被后端打回
        e.preventDefault();
        alert('验证码加载中，请稍等 1–2 秒或刷新页面后重试。');
        return;
      }
    }

    if (passwordRsaEl.value) return;
    if (!passwordEl.value) return;

    meta = meta || await metaPromise;
    if (!meta || !meta.publicKeyPem || !meta.nonce) {
      // 后端未启用 RSA：保持原提交（明文 password）
      return;
    }
    if (!hasWebCrypto) {
      if (meta.require) {
        e.preventDefault();
        alert('当前环境不支持安全登录加密，请更换浏览器或刷新重试。');
        return;
      }
      return;
    }

    e.preventDefault();
    try {
      const enc = await encryptPassword(passwordEl.value, meta.publicKeyPem, meta.nonce);
      passwordRsaEl.value = enc;
      passwordEl.value = '';
      if (meta.require) passwordEl.removeAttribute('required');
      form.submit();
    } catch (err) {
      if (meta.require) {
        alert('当前环境不支持安全登录加密，请更换浏览器或刷新重试。');
        return;
      }
      form.submit();
    }
  });
})();
</script>
