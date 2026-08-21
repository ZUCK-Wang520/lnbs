<?php declare(strict_types=1);
$verifyUrl = url('/register/verify');
?>
<div class="register-welcome" aria-live="polite">
  <div class="register-welcome-bg" aria-hidden="true"></div>
  <div class="register-welcome-inner">
    <p class="register-welcome-kicker">鲁巴校园论坛</p>
    <div class="typewriter-block">
      <p class="typewriter-line is-typing" id="twLine1"></p>
      <p class="typewriter-line typewriter-line--second" id="twLine2"></p>
    </div>
    <p class="register-welcome-hint muted" id="twHint" hidden>正在前往验证码页面…</p>
  </div>
</div>
<script>
(function(){
  var verifyUrl = <?= json_encode($verifyUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var line1 = '同学，欢迎你使用鲁巴校园论坛。';
  var line2 = '还有最后一步，您即可完成注册。';
  var el1 = document.getElementById('twLine1');
  var el2 = document.getElementById('twLine2');
  var hint = document.getElementById('twHint');
  if (!el1 || !el2) return;

  function prefersReducedMotion() {
    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function typeInto(el, text, i, delay, done) {
    if (prefersReducedMotion()) {
      el.textContent = text;
      setTimeout(done, 400);
      return;
    }
    if (i >= text.length) {
      setTimeout(done, delay);
      return;
    }
    el.textContent += text.charAt(i);
    setTimeout(function(){ typeInto(el, text, i + 1, delay, done); }, delay);
  }

  var step = 0;
  function afterLine1() {
    el1.classList.remove('is-typing');
    el2.classList.add('is-visible', 'is-typing');
    typeInto(el2, line2, 0, 42, afterLine2);
  }
  function afterLine2() {
    el2.classList.remove('is-typing');
    if (hint) { hint.hidden = false; }
    setTimeout(function(){ window.location.href = verifyUrl; }, 1200);
  }

  typeInto(el1, line1, 0, 38, afterLine1);
})();
</script>
