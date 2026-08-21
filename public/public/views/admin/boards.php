<?php declare(strict_types=1); ?>
<nav class="breadcrumb">
  <a href="<?= h(url('/admin')) ?>">后台</a>
  <span> / </span>
  <span>版块</span>
</nav>
<h1 style="margin-bottom:1rem;">版块管理</h1>

<div class="form-panel" style="max-width:100%;margin-bottom:1.5rem;">
  <h2 style="margin-top:0;font-size:1.05rem;">新建版块</h2>
  <form method="post" action="<?= h(url('/admin/boards/save')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="id" value="0">
    <div class="field">
      <label>名称</label>
      <input name="name" type="text" required maxlength="120">
    </div>
    <div class="field">
      <label>slug（URL，小写与连字符）</label>
      <input name="slug" type="text" required pattern="[a-z0-9-]+" maxlength="120" placeholder="例如：news">
    </div>
    <div class="field">
      <label>描述</label>
      <input name="description" type="text" maxlength="500">
    </div>
    <div class="field">
      <label>排序（数字越大越靠后）</label>
      <input name="sort_order" type="number" value="0">
    </div>
    <button type="submit" class="btn btn-primary">创建</button>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>名称 / 编辑</th>
        <th>slug</th>
        <th>排序</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($boards as $b) : ?>
        <tr>
          <td><?= (int) $b['id'] ?></td>
          <td>
            <form method="post" action="<?= h(url('/admin/boards/save')) ?>" style="display:flex;flex-direction:column;gap:0.5rem;">
              <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
              <input name="name" type="text" value="<?= h($b['name']) ?>" required maxlength="120">
              <input name="description" type="text" value="<?= h($b['description']) ?>" maxlength="500" placeholder="描述">
              <input name="slug" type="text" value="<?= h($b['slug']) ?>" required pattern="[a-z0-9-]+" maxlength="120">
              <input name="sort_order" type="number" value="<?= (int) $b['sort_order'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm" style="align-self:flex-start;">保存</button>
            </form>
          </td>
          <td class="muted"><?= h($b['slug']) ?></td>
          <td><?= (int) $b['sort_order'] ?></td>
          <td>
            <form method="post" action="<?= h(url('/admin/boards/delete')) ?>" onsubmit="return confirm('删除版块将删除其下所有主题，确定？');">
              <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">删除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
