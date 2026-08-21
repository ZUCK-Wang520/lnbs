<?php declare(strict_types=1);
/** @var string $pollFormId unique id prefix for inputs */
$pollFormId = $pollFormId ?? 'topic-poll';
?>
<div class="field topic-poll-compose" data-poll-form="<?= h($pollFormId) ?>">
  <label class="home-checkbox">
    <input type="checkbox" name="poll_enabled" value="1" class="js-poll-enable">
    <span>添加投票（2–10 个选项，可设置每人投票数）</span>
  </label>
  <div class="topic-poll-compose__options js-poll-options" hidden>
    <p class="muted" style="margin:0.5rem 0 0.65rem;font-size:0.88rem;">填写各选项文字，至少 2 项。</p>
    <div class="js-poll-option-list">
      <div class="field topic-poll-compose__row">
        <label>选项 1</label>
        <input type="text" name="poll_options[]" maxlength="80" placeholder="选项一">
      </div>
      <div class="field topic-poll-compose__row">
        <label>选项 2</label>
        <input type="text" name="poll_options[]" maxlength="80" placeholder="选项二">
      </div>
    </div>
    <button type="button" class="btn btn-ghost btn-sm js-poll-add-option" style="margin-top:0.35rem;">添加选项</button>
    <div class="field" style="margin-top:0.75rem;">
      <label>每人可投票数</label>
      <input type="number" name="poll_votes_per_user" class="input" min="1" max="10" value="1" style="width:5rem;">
      <p class="muted" style="margin:0.35rem 0 0;font-size:0.82rem;">每人最多 10 票，可全部投给同一选项</p>
    </div>
    <div class="field home-checkbox-field" style="margin-top:0.75rem;">
      <label class="home-checkbox">
        <input type="checkbox" name="poll_allow_user_options" value="1">
        <span>允许其他登录用户补充投票选项（人数不限，可超过 10 项）</span>
      </label>
    </div>
  </div>
</div>
<script>
(function(){
  var root = document.querySelector('.topic-poll-compose[data-poll-form="<?= h($pollFormId) ?>"]');
  if (!root) return;
  var enable = root.querySelector('.js-poll-enable');
  var panel = root.querySelector('.js-poll-options');
  var list = root.querySelector('.js-poll-option-list');
  var addBtn = root.querySelector('.js-poll-add-option');
  var maxOpts = 10;
  function syncPanel(){
    if (!enable || !panel) return;
    panel.hidden = !enable.checked;
    if (enable.checked) {
      var inputs = list ? list.querySelectorAll('input[name="poll_options[]"]') : [];
      if (inputs.length && !inputs[0].value) inputs[0].focus();
    }
  }
  if (enable) {
    enable.addEventListener('change', syncPanel);
    syncPanel();
  }
  function renumber(){
    if (!list) return;
    list.querySelectorAll('.topic-poll-compose__row').forEach(function(row, i){
      var lab = row.querySelector('label');
      if (lab) lab.textContent = '选项 ' + (i + 1);
    });
  }
  if (addBtn && list) {
    addBtn.addEventListener('click', function(){
      var n = list.querySelectorAll('input[name="poll_options[]"]').length;
      if (n >= maxOpts) {
        alert('最多 ' + maxOpts + ' 个选项');
        return;
      }
      var row = document.createElement('div');
      row.className = 'field topic-poll-compose__row';
      row.innerHTML = '<label>选项 ' + (n + 1) + '</label><input type="text" name="poll_options[]" maxlength="80" placeholder="选项">';
      list.appendChild(row);
      renumber();
      var inp = row.querySelector('input');
      if (inp) inp.focus();
    });
  }
})();
</script>
