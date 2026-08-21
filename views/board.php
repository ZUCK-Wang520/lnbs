<?php declare(strict_types=1); ?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span aria-hidden="true"> / </span>
  <span><?= h($board['name']) ?></span>
</nav>
<div class="h1-row">
  <h1><?= h($board['name']) ?></h1>
  <?php if (auth_user()) : ?>
    <a class="btn btn-primary" href="<?= h(url('/board/' . $board['slug'] . '/new')) ?>">发布主题</a>
  <?php else : ?>
    <a class="btn btn-ghost" href="<?= h(url('/login')) ?>">登录后发帖</a>
  <?php endif; ?>
</div>
<p class="muted" style="margin-top:-0.5rem;margin-bottom:1.5rem;"><?= h($board['description']) ?></p>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>主题</th>
        <th>作者</th>
        <th>回复</th>
        <th>更新</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($topics as $t) : ?>
        <tr>
          <td>
            <a href="<?= h(url('/topic/' . $t['id'])) ?>"><?= h($t['title']) ?></a>
            <?php if ((int) $t['pinned'] === 1) : ?><span class="badge badge-pin">置顶</span><?php endif; ?>
            <?php if ((int) $t['locked'] === 1) : ?><span class="badge badge-lock">锁定</span><?php endif; ?>
          </td>
          <td><?= h($t['author_nickname']) ?></td>
          <td><?= (int) $t['reply_count'] ?></td>
          <td class="muted"><?= h($t['updated_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php if (empty($topics)) : ?>
  <p class="muted" style="margin-top:1rem;">本版还没有主题，快来发第一条吧。</p>
<?php endif; ?>
