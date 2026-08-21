<?php declare(strict_types=1); ?>
<div class="form-panel">
  <h1 style="margin-top:0;font-size:1.25rem;">登记在读信息（自愿）</h1>
  <p class="muted" style="margin-top:0;">可填写在读<strong>年级</strong>、<strong>班级</strong>与<strong>真实姓名</strong>（与昵称、站内实名认证无关，便于校内核对）。<strong>不强制</strong>：若暂不愿意填写，可直接返回论坛，下次随时再来本页或到个人资料补全。示例：年级 <strong>初2027</strong>，班级 <strong>26</strong>。</p>
  <form method="post" action="<?= h(url('/login/complete-profile')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="field">
      <label for="profile_grade">年级</label>
      <input id="profile_grade" name="profile_grade" type="text" required maxlength="32" autocomplete="off" placeholder="例如：初2027">
    </div>
    <div class="field">
      <label for="profile_class">班级</label>
      <input id="profile_class" name="profile_class" type="text" required maxlength="64" autocomplete="off" placeholder="例如：26">
    </div>
    <div class="field">
      <label for="profile_real_name">真实姓名</label>
      <input id="profile_real_name" name="profile_real_name" type="text" required maxlength="32" autocomplete="name" placeholder="与身份证一致的姓名">
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;">保存</button>
  </form>
  <p class="muted" style="margin-top:1rem;margin-bottom:0;">
    <a href="<?= h(url('/')) ?>">暂不填写，返回首页</a>
    <span class="register-footer-dot">·</span>
    <a href="<?= h(url('/profile')) ?>">个人资料</a>
  </p>
  <div class="muted" style="margin-top:1rem;">
    <form class="inline-form" method="post" action="<?= h(url('/logout')) ?>" style="display:inline;">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <button type="submit" class="btn btn-ghost btn-sm">退出并改用其他账号</button>
    </form>
  </div>
</div>
