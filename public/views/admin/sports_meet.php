<?php declare(strict_types=1);
$meetId = max(0, (int) ($meetId ?? 0));
$meets = is_array($meets ?? null) ? $meets : [];
$events = is_array($events ?? null) ? $events : [];
$entries = is_array($entries ?? null) ? $entries : [];
$currentMeet = null;
foreach ($meets as $m) {
    if ((int) ($m['id'] ?? 0) === $meetId) {
        $currentMeet = $m;
        break;
    }
}
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/admin')) ?>">后台</a>
  <span> / </span>
  <span>运动会管理</span>
</nav>

<h1 style="margin:0 0 0.4rem;">运动会管理</h1>
<p class="muted" style="margin-top:0;">管理员可维护运动会整体时间、各项目时间，以及参赛学生和成绩（成绩可留空）。</p>

<section class="card" style="padding:1rem 1.1rem;margin-bottom:1rem;">
  <h2 style="margin:0 0 0.8rem;font-size:1.05rem;"><?= $currentMeet ? '编辑运动会' : '新建运动会' ?></h2>
  <form method="post" action="<?= h(url('/admin/sports-meet/save')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int) ($currentMeet['id'] ?? 0) ?>">
    <div class="field">
      <label for="sm_title">运动会名称</label>
      <input id="sm_title" name="title" type="text" maxlength="120" required value="<?= h((string) ($currentMeet['title'] ?? '')) ?>" placeholder="例如：2026 春季运动会">
    </div>
    <div class="field">
      <label for="sm_starts_at">整体开始时间</label>
      <input id="sm_starts_at" name="starts_at" type="datetime-local" required value="<?= !empty($currentMeet['starts_at']) ? h(date('Y-m-d\TH:i', strtotime((string) $currentMeet['starts_at']))) : '' ?>">
    </div>
    <div class="field">
      <label for="sm_ends_at">整体结束时间</label>
      <input id="sm_ends_at" name="ends_at" type="datetime-local" required value="<?= !empty($currentMeet['ends_at']) ? h(date('Y-m-d\TH:i', strtotime((string) $currentMeet['ends_at']))) : '' ?>">
    </div>
    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;margin:0.4rem 0 0.75rem;">
      <input type="checkbox" name="is_active" value="1"<?= !empty($currentMeet['is_active']) ? ' checked' : '' ?>>
      <span>设为当前启用的运动会</span>
    </label>
    <button type="submit" class="btn btn-primary btn-sm">保存运动会</button>
  </form>
</section>

<?php if (!empty($meets)) : ?>
<section class="card" style="padding:1rem 1.1rem;margin-bottom:1rem;">
  <h2 style="margin:0 0 0.65rem;font-size:1.05rem;">选择运动会</h2>
  <div style="display:flex;flex-wrap:wrap;gap:0.45rem;">
    <?php foreach ($meets as $m) : ?>
      <a class="btn <?= (int) $m['id'] === $meetId ? 'btn-primary' : 'btn-ghost' ?> btn-sm" href="<?= h(url('/admin/sports-meet', ['meet_id' => (int) $m['id']])) ?>">
        <?= h((string) $m['title']) ?><?= !empty($m['is_active']) ? '（启用）' : '' ?>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($meetId > 0) : ?>
<section class="card" style="padding:1rem 1.1rem;margin-bottom:1rem;">
  <h2 style="margin:0 0 0.8rem;font-size:1.05rem;">添加项目</h2>
  <form method="post" action="<?= h(url('/admin/sports-event/save')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="meet_id" value="<?= (int) $meetId ?>">
    <div class="field">
      <label for="se_name">项目名称</label>
      <input id="se_name" name="event_name" type="text" maxlength="120" required placeholder="例如：100 米短跑">
    </div>
    <div class="field">
      <label for="se_starts_at">项目开始时间</label>
      <input id="se_starts_at" name="starts_at" type="datetime-local" required>
    </div>
    <div class="field">
      <label for="se_ends_at">项目结束时间</label>
      <input id="se_ends_at" name="ends_at" type="datetime-local" required>
    </div>
    <div class="field">
      <label for="se_sort_order">排序（数字越小越靠前）</label>
      <input id="se_sort_order" name="sort_order" type="number" value="0">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">添加项目</button>
  </form>
  <?php if (!empty($events)) : ?>
    <div style="margin-top:0.8rem;">
      <?php foreach ($events as $ev) : ?>
      <div style="display:flex;gap:0.55rem;align-items:center;justify-content:space-between;border-top:1px solid var(--border);padding:0.55rem 0;">
        <div>
          <strong><?= h((string) $ev['event_name']) ?></strong>
          <div class="muted" style="font-size:0.84rem;"><?= h((string) $ev['starts_at']) ?> - <?= h((string) $ev['ends_at']) ?></div>
        </div>
        <form method="post" action="<?= h(url('/admin/sports-event/delete')) ?>" class="inline-form">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="meet_id" value="<?= (int) $meetId ?>">
          <input type="hidden" name="id" value="<?= (int) $ev['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm">删除</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if (!empty($events)) : ?>
<section class="card" style="padding:1rem 1.1rem;margin-bottom:1rem;border:1px dashed var(--border);">
  <h2 style="margin:0 0 0.5rem;font-size:1.05rem;">快速导入</h2>
  <p class="muted" style="margin:0 0 0.75rem;font-size:0.9rem;">每行一条：<strong>姓名</strong> 空格 <strong>班级</strong>（可写 <code>17</code> 或 <code>17班</code>，存库时自动去掉末尾「班」）。<strong>年级</strong>由你在下方统一填写。</p>
  <form method="post" action="<?= h(url('/admin/sports-entry/import-bulk')) ?>" class="js-moderation-submit">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="meet_id" value="<?= (int) $meetId ?>">
    <div class="field">
      <label for="bulk_event_id">导入到项目</label>
      <select id="bulk_event_id" name="event_id" required>
        <option value="">请选择项目</option>
        <?php foreach ($events as $ev) : ?>
          <option value="<?= (int) $ev['id'] ?>"><?= h((string) $ev['event_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="bulk_grade">年级（本批统一，请自行填写）</label>
      <input id="bulk_grade" name="grade_name" type="text" maxlength="32" required placeholder="例如：初2027" autocomplete="off">
    </div>
    <div class="field">
      <label for="bulk_text">名单（每行：姓名 班级）</label>
      <textarea id="bulk_text" name="bulk_text" class="input" rows="8" style="width:100%;min-height:10rem;resize:vertical;" placeholder="游晨轩 17
涂耘耀 10
陈俊宇 19
刘钊烨 38" spellcheck="false"></textarea>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">开始导入</button>
  </form>
</section>
<?php endif; ?>

<section class="card" style="padding:1rem 1.1rem;">
  <h2 style="margin:0 0 0.8rem;font-size:1.05rem;">添加参赛记录</h2>
  <?php if (empty($events)) : ?>
    <p class="muted" style="margin:0;">请先添加至少一个项目，再录入参赛学生。</p>
  <?php else : ?>
  <form method="post" action="<?= h(url('/admin/sports-entry/save')) ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="meet_id" value="<?= (int) $meetId ?>">
    <div class="field">
      <label for="sp_event_id">项目</label>
      <select id="sp_event_id" name="event_id" required>
        <option value="">请选择项目</option>
        <?php foreach ($events as $ev) : ?>
          <option value="<?= (int) $ev['id'] ?>"><?= h((string) $ev['event_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="sp_grade">年级</label>
      <input id="sp_grade" name="grade_name" type="text" maxlength="32" required placeholder="例如：初2027">
    </div>
    <div class="field">
      <label for="sp_class">班级</label>
      <input id="sp_class" name="class_name" type="text" maxlength="64" required placeholder="例如：26 或 26班（会自动存成 26）">
    </div>
    <div class="field">
      <label for="sp_name">姓名</label>
      <input id="sp_name" name="student_name" type="text" maxlength="32" required placeholder="学生姓名">
    </div>
    <div class="field">
      <label for="sp_result">成绩（可不填）</label>
      <input id="sp_result" name="result_text" type="text" maxlength="120" placeholder="例如：12.33 秒 / 4分22秒">
    </div>
    <div class="field">
      <label for="sp_achievement">比赛成就（可不填）</label>
      <input id="sp_achievement" name="achievement_text" type="text" maxlength="300" placeholder="例如：晋级决赛、校纪录">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">保存参赛记录</button>
  </form>

  <?php if (!empty($entries)) : ?>
  <div style="margin-top:0.8rem;">
    <?php foreach ($entries as $row) : ?>
    <div style="border-top:1px solid var(--border);padding:0.6rem 0;display:flex;justify-content:space-between;gap:0.6rem;">
      <div>
        <div><strong><?= h((string) $row['student_name']) ?></strong>（<?= h((string) $row['grade_name']) ?> <?= h((string) $row['class_name']) ?>）</div>
        <div class="muted" style="font-size:0.84rem;"><?= h((string) $row['event_name']) ?> · <?= h((string) $row['event_starts_at']) ?></div>
        <?php if (!empty($row['result_text']) || !empty($row['achievement_text'])) : ?>
          <div class="muted" style="font-size:0.84rem;">成绩：<?= h((string) ($row['result_text'] ?? '未录入')) ?><?= !empty($row['achievement_text']) ? (' · 成就：' . h((string) $row['achievement_text'])) : '' ?></div>
        <?php endif; ?>
      </div>
      <form method="post" action="<?= h(url('/admin/sports-entry/delete')) ?>" class="inline-form">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="meet_id" value="<?= (int) $meetId ?>">
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
        <button type="submit" class="btn btn-ghost btn-sm">删除</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</section>
<?php endif; ?>
