<?php declare(strict_types=1); ?>
<nav class="breadcrumb">
  <a href="<?= h(url('/admin')) ?>">后台</a>
  <span> / </span>
  <span>用户</span>
</nav>
<h1 style="margin-bottom:1rem;">用户管理</h1>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>邮箱</th>
        <th>昵称</th>
        <th>角色</th>
        <th>状态</th>
        <th>注册时间</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $row) : ?>
        <tr>
          <td><?= (int) $row['id'] ?></td>
          <td><?= h($row['email']) ?></td>
          <td><?= h($row['nickname']) ?></td>
          <td><?= h($row['role']) ?></td>
          <td><?= (int) $row['banned'] === 1 ? '禁言' : '正常' ?></td>
          <td class="muted"><?= h($row['created_at']) ?></td>
          <td>
            <?php if ($row['role'] === 'admin') : ?>
              <span class="muted">—</span>
            <?php else : ?>
              <form class="inline-form" method="post" action="<?= h(url('/admin/users/toggle-ban')) ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <input type="hidden" name="banned" value="<?= (int) $row['banned'] === 1 ? '0' : '1' ?>">
                <button type="submit" class="btn btn-ghost btn-sm">
                  <?= (int) $row['banned'] === 1 ? '解除禁言' : '禁言' ?>
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
