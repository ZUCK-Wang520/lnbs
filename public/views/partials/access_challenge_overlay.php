<?php
declare(strict_types=1);
$acNext = function_exists('access_challenge_current_next') ? access_challenge_current_next() : '/';
$acVerifyUrl = url('/access-challenge/verify');
?>
<div id="access-challenge-overlay" class="access-challenge-overlay" role="dialog" aria-modal="true" aria-labelledby="access-challenge-title">
  <div class="access-challenge-backdrop" aria-hidden="true"></div>
  <div class="access-challenge-card">
    <h2 id="access-challenge-title" class="access-challenge-title">安全验证</h2>
    <p class="access-challenge-lead muted">检测到异常访问，请完成滑块验证后继续浏览。</p>
    <form id="access-challenge-form" method="post" action="<?= h($acVerifyUrl) ?>" class="access-challenge-form">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="next" value="<?= h($acNext) ?>">
      <input type="hidden" name="geetest_challenge" id="ac-geetest-challenge" value="">
      <input type="hidden" name="geetest_validate" id="ac-geetest-validate" value="">
      <input type="hidden" name="geetest_seccode" id="ac-geetest-seccode" value="">
    </form>
    <div id="ac-geetest-box" class="access-challenge-geetest-host" aria-live="polite"></div>
    <p class="access-challenge-status muted" id="ac-geetest-status">正在加载验证…</p>
  </div>
</div>
<script src="https://static.geetest.com/static/js/gt.0.5.0.js"></script>
<script>
(function(){
  var overlay = document.getElementById('access-challenge-overlay');
  var form = document.getElementById('access-challenge-form');
  var st = document.getElementById('ac-geetest-status');
  var gtChal = document.getElementById('ac-geetest-challenge');
  var gtVal = document.getElementById('ac-geetest-validate');
  var gtSec = document.getElementById('ac-geetest-seccode');
  var box = document.getElementById('ac-geetest-box');
  if (!overlay || !form || !gtChal || !gtVal || !gtSec || !box) return;
  document.body.classList.add('access-challenge-open');
  var geetestRegisterUrl = <?= json_encode(url('/geetest/register'), JSON_UNESCAPED_SLASHES) ?>;
  var captchaObj = null;
  var inFlight = false;
  var ready = false;
  function setStatus(t){ if(st) st.textContent = t; }
  function submitForm(){
    if (typeof form.requestSubmit === 'function') form.requestSubmit();
    else form.submit();
  }
  function initGt(){
    if (ready || inFlight) return;
    if (typeof window.initGeetest !== 'function') return;
    inFlight = true;
    var sep = geetestRegisterUrl.indexOf('?') >= 0 ? '&' : '?';
    fetch(geetestRegisterUrl + sep + 't=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' })
      .then(function(r){ return r.ok ? r.json() : null; })
      .then(function(data){
        if (!data || !data.gt || !data.challenge) {
          inFlight = false;
          setStatus('验证加载失败，请刷新页面重试。');
          return;
        }
        // 在 initGeetest 回调执行前保持 inFlight，否则 setInterval 会再跑一遍 initGt 导致双实例。
        try {
          window.initGeetest({
            gt: data.gt,
            challenge: data.challenge,
            offline: !data.success,
            new_captcha: true,
            product: 'embed',
            width: '100%',
            https: true,
            api_server: 'api.geevisit.com'
          }, function(obj){
            if (ready) {
              inFlight = false;
              return;
            }
            captchaObj = obj;
            if (!captchaObj.__lnbsAcBound) {
              captchaObj.__lnbsAcBound = true;
              captchaObj.onSuccess(function(){
                var v = captchaObj.getValidate && captchaObj.getValidate();
                if (v) {
                  gtChal.value = v.geetest_challenge || '';
                  gtVal.value = v.geetest_validate || '';
                  gtSec.value = v.geetest_seccode || '';
                }
                document.body.classList.remove('access-challenge-open');
                submitForm();
              });
            }
            try {
              box.innerHTML = '';
              captchaObj.appendTo('#ac-geetest-box');
            } catch (e) {
              inFlight = false;
              setStatus('验证组件加载失败，请刷新页面。');
              return;
            }
            ready = true;
            inFlight = false;
            setStatus('请拖动滑块完成验证。');
          });
        } catch (e) {
          inFlight = false;
          setStatus('验证初始化失败，请刷新页面。');
        }
      })
      .catch(function(){ inFlight = false; setStatus('网络异常，请刷新页面重试。'); });
  }
  initGt();
  var tries = 0;
  var poll = setInterval(function(){
    tries++;
    initGt();
    if (ready || tries > 80) clearInterval(poll);
  }, 150);
})();
</script>
