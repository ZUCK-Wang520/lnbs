<?php declare(strict_types=1); ?>
<h1 style="margin-bottom:0.5rem;">管理后台</h1>
<p class="muted" style="margin-top:0;margin-bottom:1.75rem;">维护版块与用户秩序。</p>
<div class="admin-grid">
  <div class="admin-tile">
    <h3>版块管理</h3>
    <p>创建、编辑或删除版块。</p>
    <a class="btn btn-primary btn-sm" href="<?= h(url('/admin/boards')) ?>">进入</a>
  </div>
  <div class="admin-tile">
    <h3>用户管理</h3>
    <p>禁言违规账号（管理员除外）。</p>
    <a class="btn btn-primary btn-sm" href="<?= h(url('/admin/users')) ?>">进入</a>
  </div>
</div>
<p class="muted" style="font-size:0.88rem;">提示：在主题页可删除整条主题或单条回复。</p>
