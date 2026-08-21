<?php declare(strict_types=1);
$tc = (int) ($stats['topic_total'] ?? 0);
$pc = (int) ($stats['post_total'] ?? 0);
$me = auth_user();
$seeRealNick = $me && user_can_view_anonymous_real_identity($me);
$birthdayTodayUsers = is_array($birthdayTodayUsers ?? null) ? $birthdayTodayUsers : [];
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

<?php if (!empty($birthdayTodayUsers)) : ?>
<section class="home-birthday-celebration" aria-label="今日生日祝福">
  <div class="home-birthday-inner">
    <div class="home-birthday-glow" aria-hidden="true"></div>
    <div class="home-birthday-confetti" aria-hidden="true"></div>
    <div class="home-birthday-content">
      <p class="home-birthday-kicker">🎂 今日寿星</p>
      <h2 class="home-birthday-title">生日快乐</h2>
      <p class="home-birthday-lead">全站同学一起送上祝福，愿你新的一岁平安喜乐、万事顺意。</p>
      <ul class="home-birthday-chips">
        <?php foreach ($birthdayTodayUsers as $bu) : ?>
          <?php
            $bid = (int) ($bu['id'] ?? 0);
            $bn = (string) ($bu['nickname'] ?? '');
            $bav = $bid > 0 ? user_avatar_public_url($bu['avatar'] ?? null) : null;
            $bini = $bn !== '' ? mb_substr($bn, 0, 1, 'UTF-8') : '?';
          ?>
          <li>
            <a class="home-birthday-chip" href="<?= h(url('/user/' . $bid . '/topics')) ?>">
              <span class="home-birthday-chip-av">
                <?php if ($bav) : ?>
                  <img src="<?= h($bav) ?>" alt="" width="36" height="36" loading="lazy">
                <?php else : ?>
                  <span class="home-birthday-chip-fallback"><?= h($bini) ?></span>
                <?php endif; ?>
              </span>
              <span class="home-birthday-chip-name"><?= h($bn) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
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
              <?php if ($seeRealNick && !empty($r['author_real_nickname'])) : ?>
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

<?php if (!empty($homeHotBoardEnabled)) : ?>
<?php
  $hv = is_array($homeHotByViews ?? null) ? $homeHotByViews : [];
  $hl = is_array($homeHotByLikes ?? null) ? $homeHotByLikes : [];
  $hotRfSec = (int) ($homeHotBoardRefreshSeconds ?? 0);
  $hotRfLine = '';
  if ($hotRfSec >= 60 && $hotRfSec % 60 === 0) {
      $hotRfLine = '榜单约每 ' . (int) ($hotRfSec / 60) . ' 分钟从数据库自动刷新。';
  } elseif ($hotRfSec > 0) {
      $hotRfLine = '榜单约每 ' . $hotRfSec . ' 秒从数据库自动刷新。';
  }
?>
<section class="home-hot-board" aria-label="热门榜">
  <h2 class="home-section-title">热门榜</h2>
  <?php if (!empty($homeHotBoardViewsCumulative)) : ?>
  <p class="muted home-hot-board-lead">统计<strong>本周</strong><?= !empty($homeHotBoardWeekLabel) ? '（' . h((string) $homeHotBoardWeekLabel) . '）' : '' ?>内新增点赞最多的主题前十名。<strong>左侧</strong>因本周尚无足够「逐次浏览」记录，暂按<strong>全站累计浏览量</strong>排序；有人打开主题页后才会写入逐次记录，之后会逐渐以本周打开次数为准。换周后重算<?= !empty($homeHotBoardUpdatedLabel) ? '（上次更新 ' . h((string) $homeHotBoardUpdatedLabel) . '）' : '' ?>。<?= $hotRfLine !== '' ? h($hotRfLine) : '' ?></p>
  <?php else : ?>
  <p class="muted home-hot-board-lead">统计<strong>本周</strong><?= !empty($homeHotBoardWeekLabel) ? '（' . h((string) $homeHotBoardWeekLabel) . '）' : '' ?>内逐次浏览与新增点赞最多的主题各前十名；换周后按新一周数据重算<?= !empty($homeHotBoardUpdatedLabel) ? '（上次更新 ' . h((string) $homeHotBoardUpdatedLabel) . '）' : '' ?>。<?= $hotRfLine !== '' ? h($hotRfLine) : '' ?></p>
  <?php endif; ?>
  <div class="home-hot-board-grid">
    <div class="home-hot-board-col">
      <h3 class="home-hot-board-subtitle"><?= !empty($homeHotBoardViewsCumulative) ? '浏览最多（累计）' : '本周浏览最多' ?></h3>
      <?php if ($hv === []) : ?>
        <p class="muted home-hot-board-empty">暂无上榜主题。</p>
      <?php else : ?>
        <ul class="home-hot-board-list">
          <?php foreach ($hv as $i => $row) : ?>
            <li class="home-hot-board-item" style="--i: <?= (int) $i ?>">
              <a class="home-hot-board-link" href="<?= h(url('/topic/' . (int) $row['id'])) ?>">
                <span class="home-hot-board-rank"><?= (int) $i + 1 ?></span>
                <span class="home-hot-board-main">
                  <span class="home-hot-board-title"><?= h((string) $row['title']) ?></span>
                  <span class="home-hot-board-meta muted"><?= h((string) $row['board_name']) ?></span>
                </span>
                <span class="home-hot-board-stat"><?= !empty($homeHotBoardViewsCumulative) ? '累计 ' : '' ?><?= (int) ($row['week_views'] ?? 0) ?> 次浏览</span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div class="home-hot-board-col">
      <h3 class="home-hot-board-subtitle">本周点赞最多</h3>
      <?php if ($hl === []) : ?>
        <p class="muted home-hot-board-empty">暂无点赞记录。</p>
      <?php else : ?>
        <ul class="home-hot-board-list">
          <?php foreach ($hl as $i => $row) : ?>
            <li class="home-hot-board-item" style="--i: <?= (int) $i ?>">
              <a class="home-hot-board-link" href="<?= h(url('/topic/' . (int) $row['id'])) ?>">
                <span class="home-hot-board-rank"><?= (int) $i + 1 ?></span>
                <span class="home-hot-board-main">
                  <span class="home-hot-board-title"><?= h((string) $row['title']) ?></span>
                  <span class="home-hot-board-meta muted"><?= h((string) $row['board_name']) ?></span>
                </span>
                <span class="home-hot-board-stat"><?= (int) ($row['week_likes'] ?? 0) ?> 赞</span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
$gitUpdates = is_array($gitUpdates ?? null) ? $gitUpdates : [];
$gitCommitBaseUrl = trim((string) ($gitCommitBaseUrl ?? ''));
$gitUpdatesCacheTtl = max(30, (int) ($gitUpdatesCacheTtl ?? 300));
?>
<?php if ($gitUpdates !== []) : ?>
<section class="home-git-updates" aria-label="站点更新">
  <h2 class="home-section-title">站点更新</h2>
  <p class="muted home-git-lead"><?php
    $ghSrc = function_exists('git_updates_config') && git_updates_config()['source'] === 'github';
    echo $ghSrc ? '以下为 GitHub 仓库最近 3 次提交（通过 API 拉取）' : '以下为代码仓库最近 3 次提交';
  ?>，约每 <?= (int) $gitUpdatesCacheTtl ?> 秒刷新一次缓存。</p>
  <ul class="home-git-list">
    <?php foreach ($gitUpdates as $gc) : ?>
      <?php
        $gh = (string) ($gc['hash'] ?? '');
        $gs = (string) ($gc['subject'] ?? '');
        $gdt = (string) ($gc['date'] ?? '');
        $ga = (string) ($gc['author'] ?? '');
        $gshort = $gh !== '' ? substr($gh, 0, 7) : '';
        $gurl = ($gitCommitBaseUrl !== '' && $gh !== '') ? ($gitCommitBaseUrl . '/' . rawurlencode($gh)) : '';
      ?>
      <li class="home-git-item">
        <div class="home-git-row">
          <?php if ($gurl !== '') : ?>
            <a class="home-git-subject" href="<?= h($gurl) ?>" target="_blank" rel="noopener noreferrer"><?= h($gs !== '' ? $gs : $gshort) ?></a>
          <?php else : ?>
            <span class="home-git-subject"><?= h($gs !== '' ? $gs : $gshort) ?></span>
          <?php endif; ?>
          <code class="home-git-hash" title="<?= h($gh) ?>"><?= h($gshort) ?></code>
        </div>
        <div class="home-git-meta muted"><?= h($gdt) ?><?= $ga !== '' ? ' · ' . h($ga) : '' ?></div>
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
