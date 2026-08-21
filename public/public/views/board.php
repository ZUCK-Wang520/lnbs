<?php declare(strict_types=1);
$isAdmin = auth_user() && (auth_user()['role'] ?? '') === 'admin';
?>
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
          <td>
            <?php
              $bPub = (int) ($t['author_public_id'] ?? 0);
              $bAv = $bPub > 0 ? user_avatar_public_url($t['author_avatar'] ?? null) : null;
              $bNick = (string) $t['author_nickname'];
              $bIni = $bNick !== '' ? mb_substr($bNick, 0, 1, 'UTF-8') : '?';
            ?>
            <div class="board-author-cell">
              <?php if ($bPub > 0) : ?>
                <a href="<?= h(url('/user/' . $bPub . '/topics')) ?>" class="user-avatar-link" title="查看其全部主题">
                  <?php if ($bAv) : ?>
                    <img class="user-avatar-img user-avatar-img--board" src="<?= h($bAv) ?>" alt="" width="32" height="32" loading="lazy">
                  <?php else : ?>
                    <span class="user-avatar-fallback user-avatar-fallback--board"><?= h($bIni) ?></span>
                  <?php endif; ?>
                </a>
              <?php else : ?>
                <span class="user-avatar-link user-avatar-link--nohref" aria-hidden="true">
                  <span class="user-avatar-fallback user-avatar-fallback--board user-avatar-fallback--anon"><?= h($bIni) ?></span>
                </span>
              <?php endif; ?>
              <span class="board-author-text">
                <?= h($t['author_nickname']) ?>
                <?php if ($isAdmin && !empty($t['author_real_nickname'])) : ?>
                  <span class="badge badge-real badge-tiny">真实：<?= h((string) $t['author_real_nickname']) ?></span>
                <?php endif; ?>
              </span>
            </div>
          </td>
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
