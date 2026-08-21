<?php declare(strict_types=1);
$coupleTablesOk = !empty($coupleTablesOk);
$paired = !empty($paired);
$boundAtMs = (int) ($boundAtMs ?? 0);
$incomingInvites = is_array($incomingInvites ?? null) ? $incomingInvites : [];
$outgoingInvites = is_array($outgoingInvites ?? null) ? $outgoingInvites : [];
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>情侣空间</span>
</nav>

<?php if (!$coupleTablesOk) : ?>
  <div class="card couple-lg-setup-hint">
    <p>情侣功能尚未初始化。请在数据库中执行：</p>
    <code class="couple-lg-code">public/database/migration_couple.sql</code>
    <p class="muted" style="margin-top:0.75rem;">相册与「恋爱列表」另需执行 <code class="couple-lg-code">migration_couple_extras.sql</code>。</p>
  </div>
<?php elseif (!$paired) : ?>
  <div class="couple-lg couple-lg--solo">
    <div class="couple-lg-solo-head card">
      <h1 class="couple-lg-title">情侣空间</h1>
      <p class="muted couple-lg-sub">向心仪的 Ta 发出绑定邀请，对方同意后即可开启双人小站。</p>
      <p class="muted couple-lg-sub" style="margin-top:0.35rem;font-size:0.88rem;">在任意用户的「公开主题」页可找到「邀请绑定情侣」入口。</p>
    </div>
    <?php if ($incomingInvites !== []) : ?>
      <section class="card couple-lg-invite-block">
        <h2 class="couple-lg-section-title">收到的邀请</h2>
        <ul class="couple-lg-invite-list">
          <?php foreach ($incomingInvites as $inv) : ?>
            <li class="couple-lg-invite-item">
              <div>
                <strong><?= h((string) ($inv['from_nickname'] ?? '')) ?></strong>
                <span class="muted" style="font-size:0.85rem;"> · <?= h((string) ($inv['created_at'] ?? '')) ?></span>
                <?php if (trim((string) ($inv['message'] ?? '')) !== '') : ?>
                  <p class="couple-lg-invite-msg"><?= h(trim((string) $inv['message'])) ?></p>
                <?php endif; ?>
              </div>
              <div class="couple-lg-invite-actions">
                <form method="post" action="<?= h(url('/couple/respond')) ?>" class="inline-form">
                  <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="invite_id" value="<?= (int) ($inv['id'] ?? 0) ?>">
                  <input type="hidden" name="accept" value="1">
                  <button type="submit" class="btn btn-primary btn-sm">接受</button>
                </form>
                <form method="post" action="<?= h(url('/couple/respond')) ?>" class="inline-form">
                  <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                  <input type="hidden" name="invite_id" value="<?= (int) ($inv['id'] ?? 0) ?>">
                  <button type="submit" class="btn btn-ghost btn-sm">拒绝</button>
                </form>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>
    <?php if ($outgoingInvites !== []) : ?>
      <section class="card couple-lg-invite-block">
        <h2 class="couple-lg-section-title">已发出的邀请</h2>
        <ul class="couple-lg-invite-list">
          <?php foreach ($outgoingInvites as $inv) : ?>
            <li class="couple-lg-invite-item">
              <div>
                等待 <strong><?= h((string) ($inv['to_nickname'] ?? '')) ?></strong> 回应
                <span class="muted" style="font-size:0.85rem;"> · <?= h((string) ($inv['created_at'] ?? '')) ?></span>
              </div>
              <form method="post" action="<?= h(url('/couple/cancel-invite')) ?>" class="inline-form" onsubmit="return confirm('确定撤销该邀请？');">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="invite_id" value="<?= (int) ($inv['id'] ?? 0) ?>">
                <button type="submit" class="btn btn-ghost btn-sm">撤销</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>
  </div>
<?php else : ?>
  <?php
    $p = is_array($partner ?? null) ? $partner : null;
    $love = trim((string) (($coupleRow['love_note'] ?? '') ?: ''));
  ?>
  <div class="couple-lg couple-lg--paired" data-bound-ms="<?= $boundAtMs ?>">
    <!-- 参照 LikeGirl 首页：时间区 -->
    <div class="couple-lg-time card">
      <div class="couple-lg-time-line">
        <span id="coupleLgClock" class="couple-lg-clock"></span>
      </div>
      <div class="couple-lg-time-together" aria-live="polite">
        <span class="couple-lg-together-label">相恋时光</span>
        <span class="couple-lg-together-nums">
          <b id="coupleLgDay">0</b><span class="couple-lg-unit">天</span>
          <b id="coupleLgHour">0</b><span class="couple-lg-unit">时</span>
          <b id="coupleLgMin">0</b><span class="couple-lg-unit">分</span>
          <b id="coupleLgSec">0</b><span class="couple-lg-unit">秒</span>
        </span>
      </div>
      <?php if ($p) : ?>
        <p class="couple-lg-names muted">
          <?= h((string) ($u['nickname'] ?? '')) ?> · <?= h((string) $p['nickname']) ?>
        </p>
      <?php endif; ?>
      <?php if ($love !== '') : ?>
        <p class="couple-lg-love-preview">「<?= h($love) ?>」</p>
      <?php endif; ?>
    </div>

    <!-- 卡片区：与 LikeGirl index 导航卡片对应 -->
    <div class="couple-lg-card-wrap">
      <div class="couple-lg-row couple-lg-row--3">
        <a class="couple-lg-card couple-lg-card--sm couple-lg-card--fade" href="<?= h(url('/couple/little')) ?>">
          <span class="couple-lg-card-icon couple-lg-card-icon--home" aria-hidden="true"></span>
          <div class="couple-lg-card-text">
            <span class="couple-lg-card-title">点点滴滴</span>
            <p class="couple-lg-card-desc">记录一句心里话</p>
          </div>
        </a>
        <a class="couple-lg-card couple-lg-card--sm couple-lg-card--fade" href="<?= h(url('/couple/leaving')) ?>">
          <span class="couple-lg-card-icon couple-lg-card-icon--msg" aria-hidden="true"></span>
          <div class="couple-lg-card-text">
            <span class="couple-lg-card-title">留言祝福</span>
            <p class="couple-lg-card-desc">给 Ta 留一段话</p>
          </div>
        </a>
        <a class="couple-lg-card couple-lg-card--sm couple-lg-card--fade" href="<?= h(url('/couple/about')) ?>">
          <span class="couple-lg-card-icon couple-lg-card-icon--about" aria-hidden="true"></span>
          <div class="couple-lg-card-text">
            <span class="couple-lg-card-title">关于我们</span>
            <p class="couple-lg-card-desc">头像与相伴天数</p>
          </div>
        </a>
      </div>
      <div class="couple-lg-row couple-lg-row--2">
        <a class="couple-lg-card couple-lg-card--lg couple-lg-card--fade" href="<?= h(url('/couple/album')) ?>">
          <span class="couple-lg-card-icon couple-lg-card-icon--photo" aria-hidden="true"></span>
          <div class="couple-lg-card-text">
            <span class="couple-lg-card-title">Love Photo</span>
            <p class="couple-lg-card-desc">恋爱相册 · 记录最美瞬间</p>
          </div>
        </a>
        <a class="couple-lg-card couple-lg-card--lg couple-lg-card--fade" href="<?= h(url('/couple/list')) ?>">
          <span class="couple-lg-card-icon couple-lg-card-icon--list" aria-hidden="true"></span>
          <div class="couple-lg-card-text">
            <span class="couple-lg-card-title">Love List</span>
            <p class="couple-lg-card-desc">恋爱列表 · 你我之间的约定</p>
          </div>
        </a>
      </div>
    </div>

    <div class="card couple-lg-unbind-wrap">
      <form method="post" action="<?= h(url('/couple/unbind')) ?>" class="inline-form" onsubmit="return confirm('确定解除情侣绑定？双人数据将保留至对方也解除或管理员清理。');">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <button type="submit" class="btn btn-ghost btn-sm">解除绑定</button>
      </form>
    </div>
  </div>
  <script>
(function(){
  var root=document.querySelector('.couple-lg--paired'); if(!root)return;
  var ms=parseInt(root.getAttribute('data-bound-ms')||'0',10)||0;
  function pad(n){return n<10?'0'+n:''+n;}
  function tickClock(){
    var d=new Date();
    var el=document.getElementById('coupleLgClock');
    if(el) el.textContent=d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+' '+pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds());
  }
  function tickTogether(){
    if(!ms)return;
    var diff=Date.now()-ms;
    if(diff<0)diff=0;
    var s=Math.floor(diff/1000);
    var day=Math.floor(s/86400);
    s-=day*86400;
    var hour=Math.floor(s/3600);
    s-=hour*3600;
    var min=Math.floor(s/60);
    var sec=s-min*60;
    var d=document.getElementById('coupleLgDay'),h=document.getElementById('coupleLgHour'),m=document.getElementById('coupleLgMin'),sc=document.getElementById('coupleLgSec');
    if(d)d.textContent=String(day);
    if(h)h.textContent=String(hour);
    if(m)m.textContent=String(min);
    if(sc)sc.textContent=String(sec);
  }
  tickClock(); tickTogether();
  setInterval(tickClock,250);
  setInterval(tickTogether,250);
})();
  </script>
<?php endif; ?>
