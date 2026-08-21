<?php declare(strict_types=1);
$tc = (int) ($stats['topic_total'] ?? 0);
$pc = (int) ($stats['post_total'] ?? 0);
$me = auth_user();
$isAdmin = $me && ($me['role'] ?? '') === 'admin';
?>
<div class="home-hero">
  <div class="home-hero-bg" aria-hidden="true"></div>
  <div class="home-hero-content">
    <p class="home-kicker">鲁巴校园论坛</p>
    <h1 class="home-title">分享观点，遇见同频</h1>
    <p class="home-lead">学习交流、活动交友、新生指引——登录后可发帖与回复；支持<strong>匿名展示</strong>，管理员可查看真实账号。</p>
    <div class="home-stats">
      <div class="home-stat home-stat-a">
        <span class="home-stat-num"><?= $tc ?></span>
        <span class="home-stat-label">主题</span>
      </div>
      <div class="home-stat home-stat-b">
        <span class="home-stat-num"><?= $pc ?></span>
        <span class="home-stat-label">回复</span>
      </div>
      <div class="home-stat home-stat-c">
        <span class="home-stat-num"><?= count($boards) ?></span>
        <span class="home-stat-label">版块</span>
      </div>
      <div class="home-stat home-stat-d">
        <span class="home-stat-num"><?= online_count() ?></span>
        <span class="home-stat-label">在线</span>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($boards)) : ?>
<section class="home-quick">
  <div class="home-quick-head">
    <h2>快速发帖</h2>
    <p class="muted">请先登录。可选择匿名发布：列表与主题中显示匿名昵称，站长/管理员可见发帖人真实昵称。</p>
  </div>
  <div class="form-panel home-quick-form">
    <?php if ($me) : ?>
    <form method="post" action="<?= h(url('/topic/quick')) ?>" class="js-moderation-submit">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="field">
        <label for="hq_board">版块</label>
        <select id="hq_board" name="board_slug" required>
          <?php foreach ($boards as $b) : ?>
            <option value="<?= h($b['slug']) ?>"><?= h($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="hq_title">标题</label>
        <input id="hq_title" name="title" type="text" required maxlength="200" placeholder="想聊点什么？">
      </div>
      <div class="field">
        <label for="hq_body">正文</label>
        <textarea id="hq_body" name="body" required placeholder="文明发言，友善交流…"></textarea>
      </div>
      <div class="field home-checkbox-field">
        <label class="home-checkbox">
          <input type="checkbox" name="anonymous" value="1">
          <span>以匿名身份发布（不显示我的账号昵称）</span>
        </label>
      </div>
      <div class="field">
        <label for="hq_display">匿名显示名（可选，留空为「匿名」）</label>
        <input id="hq_display" name="display_nickname" type="text" maxlength="16" placeholder="最多 16 字">
      </div>
      <button type="submit" class="btn btn-primary home-submit-btn">发布主题</button>
    </form>
    <?php else : ?>
      <p class="muted" style="margin:0;">请 <a href="<?= h(url('/login')) ?>">登录</a> 后在此快速发帖。</p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($recent)) : ?>
<section class="home-recent">
  <h2 class="home-section-title">最新动态</h2>
  <ul class="home-recent-list">
    <?php foreach ($recent as $i => $r) : ?>
      <li class="home-recent-item" style="--i: <?= (int) $i ?>">
        <?php
          $hPub = (int) ($r['author_public_id'] ?? 0);
          $hAv = $hPub > 0 ? user_avatar_public_url($r['author_avatar'] ?? null) : null;
          $hNick = (string) $r['author_nickname'];
          $hIni = $hNick !== '' ? mb_substr($hNick, 0, 1, 'UTF-8') : '?';
        ?>
        <div class="home-recent-row">
          <?php if ($hPub > 0) : ?>
            <a class="home-recent-avatar-link" href="<?= h(url('/user/' . $hPub . '/topics')) ?>" title="查看其全部主题">
              <?php if ($hAv) : ?>
                <img class="user-avatar-img user-avatar-img--home" src="<?= h($hAv) ?>" alt="" width="36" height="36" loading="lazy">
              <?php else : ?>
                <span class="user-avatar-fallback user-avatar-fallback--home"><?= h($hIni) ?></span>
              <?php endif; ?>
            </a>
          <?php else : ?>
            <span class="home-recent-avatar-link home-recent-avatar-link--static" aria-hidden="true">
              <span class="user-avatar-fallback user-avatar-fallback--home user-avatar-fallback--anon"><?= h($hIni) ?></span>
            </span>
          <?php endif; ?>
          <a class="home-recent-link" href="<?= h(url('/topic/' . (int) $r['id'])) ?>">
            <span class="home-recent-title"><?= h($r['title']) ?></span>
            <span class="home-recent-meta">
              <span class="home-recent-board"><?= h($r['board_name']) ?></span>
              <span class="home-recent-dot">·</span>
              <span><?= h($r['author_nickname']) ?></span>
              <?php if ($isAdmin && !empty($r['author_real_nickname'])) : ?>
                <span class="badge badge-real">真实：<?= h((string) $r['author_real_nickname']) ?></span>
              <?php endif; ?>
              <span class="home-recent-dot">·</span>
              <span class="muted"><?= h($r['updated_at']) ?></span>
            </span>
          </a>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section class="home-boards-section">
  <h2 class="home-section-title">浏览版块</h2>
  <div class="grid-boards home-boards-grid">
    <?php foreach ($boards as $b) : ?>
      <a href="<?= h(url('/board/' . $b['slug'])) ?>" class="card home-board-card" style="text-decoration:none;color:inherit;display:block;">
        <h2><?= h($b['name']) ?></h2>
        <div class="meta"><?= (int) $b['topic_count'] ?> 个主题</div>
        <p class="muted" style="margin:0;font-size:0.92rem;"><?= h($b['description']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php if (empty($boards)) : ?>
  <p class="muted">暂无版块，请管理员在后台创建。</p>
<?php endif; ?>
