<?php declare(strict_types=1); ?>
<div class="form-panel">
  <h1 style="margin-top:0;font-size:1.25rem;">补全学籍信息</h1>
  <p class="muted" style="margin-top:0;">请填写在读<strong>年级</strong>、<strong>班级</strong>与<strong>真实姓名</strong>（与昵称、站内实名认证无关，便于校内核对）。保存前无法浏览论坛其他页面。</p>
  <form method="post" action="<?= h(url('/login/complete-profile')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="profile_grade">年级</label>
      <input id="profile_grade" name="profile_grade" type="text" required maxlength="32" autocomplete="off" placeholder="例如：高一">
    </div>
    <div class="field">
      <label for="profile_class">班级</label>
      <input id="profile_class" name="profile_class" type="text" required maxlength="64" autocomplete="off" placeholder="例如：3 班">
    </div>
    <div class="field">
      <label for="profile_real_name">真实姓名</label>
      <input id="profile_real_name" name="profile_real_name" type="text" required maxlength="32" autocomplete="name" placeholder="与身份证一致的姓名">
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;">保存并继续</button>
  </form>
  <div class="muted" style="margin-top:1.25rem;">
    <form class="inline-form" method="post" action="<?= h(url('/logout')) ?>" style="display:inline;">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <button type="submit" class="btn btn-ghost btn-sm">退出并改用其他账号</button>
    </form>
  </div>
</div>
