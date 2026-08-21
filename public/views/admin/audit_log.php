<?php
declare(strict_types=1);
$rows = is_array($rows ?? null) ? $rows : [];
$actorOptions = is_array($actorOptions ?? null) ? $actorOptions : [];
$actorFilter = (int) ($actorFilter ?? 0);
$actionQ = (string) ($actionQ ?? '');
$page = max(1, (int) ($page ?? 1));
$pages = max(1, (int) ($pages ?? 1));
$total = (int) ($total ?? 0);
$perPage = (int) ($perPage ?? 50);
?>
<p class="muted" style="margin:0 0 1rem;">
  <a href="<?= h(url('/admin')) ?>">管理后台</a>
  <span aria-hidden="true"> · </span>
  <strong>操作审计</strong>
</p>
<h1 style="margin:0 0 0.35rem;font-size:1.45rem;">操作审计</h1>
<p class="muted" style="margin:0 0 1.25rem;line-height:1.5;font-size:0.92rem;">
  记录站长与二级管理员在后台的敏感操作（封禁、删帖、版块、复核表决等）。可按操作者筛选。
</p>

<form method="get" action="<?= h(url('/admin/audit-log')) ?>" class="admin-users-toolbar" style="margin-bottom:1.25rem;flex-wrap:wrap;gap:0.65rem;">
  <div class="field" style="margin:0;min-width:12rem;">
    <label for="audit_actor">操作者</label>
    <select id="audit_actor" name="actor" class="input" style="width:100%;">
      <option value="0">全部</option>
      <?php foreach ($actorOptions as $ao) : ?>
        <?php
        $aid = (int) ($ao['id'] ?? 0);
        $an = trim((string) ($ao['nickname'] ?? ''));
        ?>
        <option value="<?= $aid ?>"<?= $actorFilter === $aid ? ' selected' : '' ?>>
          #<?= $aid ?><?= $an !== '' ? ' ' . h($an) : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field" style="margin:0;flex:1;min-width:12rem;">
    <label for="audit_q">操作类型包含</label>
    <input id="audit_q" class="input" type="search" name="q" value="<?= h($actionQ) ?>" placeholder="如 user. 或 moderation" maxlength="64" autocomplete="off">
  </div>
  <div style="align-self:flex-end;display:flex;gap:0.45rem;flex-wrap:wrap;">
    <button type="submit" class="btn btn-primary btn-sm">筛选</button>
    <a href="<?= h(url('/admin/audit-log')) ?>" class="btn btn-ghost btn-sm">重置</a>
  </div>
</form>

<p class="muted" style="font-size:0.88rem;margin:0 0 1rem;">共 <strong><?= $total ?></strong> 条；第 <?= $page ?> / <?= $pages ?> 页</p>

<?php if ($rows === []) : ?>
  <p class="muted card" style="padding:1.25rem;">暂无记录。执行后台操作后会自动写入。</p>
<?php else : ?>
  <ul class="admin-user-list" style="gap:0.65rem;">
    <?php foreach ($rows as $r) : ?>
      <?php
      $mid = (int) ($r['id'] ?? 0);
      $aid = (int) ($r['actor_user_id'] ?? 0);
      $anick = trim((string) ($r['actor_nickname'] ?? ''));
      $act = (string) ($r['action'] ?? '');
      $sum = trim((string) ($r['summary'] ?? ''));
      $ip = trim((string) ($r['ip'] ?? ''));
      $rp = trim((string) ($r['request_path'] ?? ''));
      $ca = (string) ($r['created_at'] ?? '');
      $mj = trim((string) ($r['meta_json'] ?? ''));
      ?>
      <li class="admin-user-card" style="padding:0;">
        <div class="admin-user-card__body" style="padding:0.85rem 1rem;">
          <div style="display:flex;flex-wrap:wrap;gap:0.35rem 0.75rem;align-items:baseline;justify-content:space-between;">
            <span class="admin-user-card__id">#<?= $mid ?></span>
            <time class="muted" style="font-size:0.82rem;" datetime="<?= h($ca) ?>"><?= h($ca) ?></time>
          </div>
          <p style="margin:0.35rem 0 0;font-weight:650;">
            <span class="admin-pill admin-pill--mono" style="font-size:0.78rem;"><?= h($act) ?></span>
          </p>
          <p style="margin:0.4rem 0 0;font-size:0.9rem;">
            操作者 <strong>#<?= $aid ?></strong><?= $anick !== '' ? ' ' . h($anick) : '' ?>
            <?php if ($ip !== '') : ?>
              <span class="muted" style="font-size:0.85rem;"> · IP <?= h($ip) ?></span>
            <?php endif; ?>
          </p>
          <?php if ($sum !== '') : ?>
            <p style="margin:0.35rem 0 0;font-size:0.9rem;color:var(--text);"><?= h($sum) ?></p>
          <?php endif; ?>
          <?php if ($rp !== '') : ?>
            <p class="muted" style="margin:0.25rem 0 0;font-size:0.8rem;word-break:break-all;"><?= h($rp) ?></p>
          <?php endif; ?>
          <?php if ($mj !== '') : ?>
            <details class="admin-user-more" style="margin-top:0.5rem;">
              <summary>JSON 详情</summary>
              <div class="inner">
                <pre style="margin:0;white-space:pre-wrap;word-break:break-all;font-size:0.78rem;max-height:14rem;overflow:auto;"><?= h($mj) ?></pre>
              </div>
            </details>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php
$qparams = [];
if ($actorFilter > 0) {
    $qparams['actor'] = $actorFilter;
}
if ($actionQ !== '') {
    $qparams['q'] = $actionQ;
}
?>
<?php if ($pages > 1) : ?>
  <nav class="admin-users-pager" style="margin-top:1.25rem;" aria-label="分页">
    <?php if ($page > 1) : ?>
      <a href="<?= h(url('/admin/audit-log', array_merge($qparams, ['page' => $page - 1]))) ?>">上一页</a>
    <?php endif; ?>
    <span class="muted" style="margin:0 0.5rem;">第 <?= $page ?> / <?= $pages ?> 页</span>
    <?php if ($page < $pages) : ?>
      <a href="<?= h(url('/admin/audit-log', array_merge($qparams, ['page' => $page + 1]))) ?>">下一页</a>
    <?php endif; ?>
  </nav>
<?php endif; ?>

<p style="margin-top:1.5rem;"><a href="<?= h(url('/admin')) ?>" class="btn btn-ghost btn-sm">返回后台首页</a></p>
