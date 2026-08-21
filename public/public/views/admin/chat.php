<?php declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
/** @var int $page */
/** @var int $pages */
/** @var int $total */
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/admin')) ?>">管理后台</a>
  <span> / </span>
  <span>私信审计</span>
</nav>

<h1 style="margin-top:0;">私信审计</h1>
<p class="muted" style="margin-top:-0.25rem;">全站用户私信内容（按时间倒序）。共 <?= (int) $total ?> 条。</p>

<?php if (empty($rows)) : ?>
  <p class="muted">暂无消息。</p>
<?php else : ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>时间</th>
          <th>发送者</th>
          <th>接收者</th>
          <th>内容</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r) : ?>
          <tr>
            <td class="muted"><?= (int) $r['id'] ?></td>
            <td class="muted" style="white-space:nowrap;"><?= h((string) $r['created_at']) ?></td>
            <td><?= h((string) $r['from_nickname']) ?> <span class="muted">#<?= (int) $r['from_user_id'] ?></span></td>
            <td><?= h((string) $r['to_nickname']) ?> <span class="muted">#<?= (int) $r['to_user_id'] ?></span></td>
            <td style="max-width:28rem;white-space:pre-wrap;word-break:break-word;"><?= h((string) $r['body']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1) : ?>
    <p class="muted" style="margin-top:1rem;">
      第 <?= (int) $page ?> / <?= (int) $pages ?> 页
      <?php if ($page > 1) : ?>
        <a href="<?= h(url('/admin/chat', ['page' => $page - 1])) ?>">上一页</a>
      <?php endif; ?>
      <?php if ($page < $pages) : ?>
        <a href="<?= h(url('/admin/chat', ['page' => $page + 1])) ?>">下一页</a>
      <?php endif; ?>
    </p>
  <?php endif; ?>
<?php endif; ?>

<p style="margin-top:1.5rem;"><a href="<?= h(url('/admin')) ?>" class="btn btn-ghost btn-sm">返回后台首页</a></p>
