<?php
declare(strict_types=1);

if (!function_exists('geetest_enabled') || !geetest_enabled()) {
    return;
}
$formId = (string) ($geetest_form_id ?? '');
if ($formId === '') {
    return;
}
?>
<input type="hidden" name="geetest_challenge" id="geetest_challenge" value="">
<input type="hidden" name="geetest_validate" id="geetest_validate" value="">
<input type="hidden" name="geetest_seccode" id="geetest_seccode" value="">
<script src="https://static.geetest.com/static/js/gt.0.5.0.js"></script>
<script>
(() => {
  const form = document.getElementById(<?= json_encode($formId, JSON_UNESCAPED_SLASHES) ?>);
  if (!form) return;
  const submitBtn = form.querySelector('button[type="submit"],input[type="submit"]');
  const gtChalEl = form.querySelector('#geetest_challenge');
  const gtValEl = form.querySelector('#geetest_validate');
  const gtSecEl = form.querySelector('#geetest_seccode');
  if (!gtChalEl || !gtValEl || !gtSecEl) return;

  function safeRequestSubmit() {
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
      return;
    }
    if (submitBtn && typeof submitBtn.click === 'function') {
      submitBtn.click();
      return;
    }
    form.submit();
  }

  const geetestRegisterUrl = <?= json_encode(url('/geetest/register'), JSON_UNESCAPED_SLASHES) ?>;
  let captchaObj = null;
  let inFlight = false;
  let ok = false;

  function init() {
    if (ok || inFlight) return;
    if (typeof window.initGeetest !== 'function') return;
    inFlight = true;
    const sep = geetestRegisterUrl.indexOf('?') >= 0 ? '&' : '?';
    fetch(geetestRegisterUrl + sep + 't=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
      .then(r => r.ok ? r.json() : null)
      .then(data => {
        inFlight = false;
        if (!data || !data.gt || !data.challenge) return;
        window.initGeetest({
          gt: data.gt,
          challenge: data.challenge,
          offline: !data.success,
          new_captcha: true,
          product: 'bind',
          https: true,
          api_server: 'api.geetest.com'
        }, (obj) => {
          captchaObj = obj;
          ok = true;
        });
      })
      .catch(() => { inFlight = false; });
  }

  init();
  let tries = 0;
  const poll = setInterval(() => {
    tries++;
    init();
    if (ok || tries > 80) clearInterval(poll);
  }, 150);

  form.addEventListener('submit', (e) => {
    if (gtChalEl.value && gtValEl.value && gtSecEl.value) return;
    if (captchaObj && typeof captchaObj.verify === 'function') {
      e.preventDefault();
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
    e.preventDefault();
    alert('验证码加载中，请稍等 1–2 秒或刷新页面后重试。');
  });
})();
</script>

