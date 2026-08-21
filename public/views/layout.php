<?php
declare(strict_types=1);
$appName = $GLOBALS['APP_CONFIG']['app']['name'] ?? '论坛';
$icpRecord = trim((string) ($GLOBALS['APP_CONFIG']['app']['icp_record'] ?? ''));
$u = auth_user();
$flash = flash_get();
$levelUpOverlay = empty($layout_minimal ?? false) ? user_level_up_consume_overlay() : null;
$checkinCelebration = empty($layout_minimal ?? false) ? user_checkin_celebration_consume() : null;
if ($u && user_level_columns_ok() && !isset($_SESSION['user_level_display'])) {
    user_level_refresh_session_cache((int) $u['id']);
}
$navUserLevel = ($u && user_level_columns_ok() && isset($_SESSION['user_level_display'])) ? (int) $_SESSION['user_level_display'] : null;
$isAdminSection = $isAdminSection ?? false;
$onlineCount = online_count();
$layout_minimal = !empty($layout_minimal ?? false);
$layout_minimal_mode = (string) ($layout_minimal_mode ?? 'register');
$siteLogoUrl = site_logo_url();
$confessUnread = $u ? confession_unread_count((int) $u['id']) : 0;
$askUnread = ($u && function_exists('anon_ask_owner_unread_total')) ? anon_ask_owner_unread_total((int) $u['id']) : 0;
$chatPending = ($u && chat_tables_ok()) ? chat_nav_badge_total((int) $u['id']) : 0;
$messagesTableOk = $u ? topic_reply_notifications_table_ok() : false;
$messagesUnread = ($u && $messagesTableOk) ? topic_reply_notifications_unread_count((int) $u['id']) : 0;
$chatUnread = ($u && chat_tables_ok()) ? chat_unread_message_count((int) $u['id']) : 0;
$coupleInvitePending = ($u && function_exists('couple_tables_ok') && couple_tables_ok())
    ? couple_incoming_pending_count((int) $u['id'])
    : 0;
$messagesNavBadgeTotal = $messagesUnread + $coupleInvitePending + $chatUnread;
$__p = request_path();
$confessWriteActive = $__p === '/confessions/new' || $__p === '/confessions/sent';
$confessInboxActive = $__p === '/confessions' || (bool) preg_match('#^/confession/\d+$#', $__p);
$chatNavActive = $__p === '/chat' || (bool) preg_match('#^/chat/with/\d+$#', $__p);
$coupleNavActive = $__p === '/couple' || str_starts_with($__p, '/couple/');
$askNavActive = $__p === '/ask' || str_starts_with($__p, '/ask/');
$messagesNavActive = $__p === '/messages';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <script>
(function(){try{var k='luba-theme',t=localStorage.getItem(k);if(t!=='light'&&t!=='dark')t='dark';document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();
  </script>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="dark light">
  <title><?= h($pageTitle) ?> · <?= h($appName) ?></title>
  <style>
<?php
$__styleFile = dirname(__DIR__) . '/assets/theme.css';
if (is_readable($__styleFile)) {
    readfile($__styleFile);
} else {
    echo '/* 缺少 public/assets/theme.css，请上传该文件 */';
}
?>
  </style>
</head>
<body<?= $layout_minimal ? ' class="is-layout-minimal' . ($layout_minimal_mode === 'shutdown' ? ' is-shutdown-page' : '') . '"' : '' ?>>
  <header class="topbar">
    <div class="topbar-inner">
      <a href="<?= h(url('/')) ?>" class="brand">
        <?php if ($siteLogoUrl) : ?>
          <img class="brand-logo" src="<?= h($siteLogoUrl) ?>" alt="<?= h($appName) ?>" width="40" height="40" decoding="async" fetchpriority="high">
        <?php else : ?>
          <span class="brand-mark" aria-hidden="true"></span>
        <?php endif; ?>
        <span class="brand-text"><?= h($appName) ?></span>
      </a>
      <?php if ($layout_minimal) : ?>
      <nav class="nav-links" aria-label="主导航">
        <button type="button" class="theme-toggle" id="themeToggle" aria-label="切换日间或夜间模式" title="日间 / 夜间">
          <span class="theme-toggle-track">
            <span class="theme-toggle-thumb">
              <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
              <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </span>
          </span>
        </button>
        <?php if ($layout_minimal_mode === 'shutdown') : ?>
        <button type="button" class="btn btn-ghost btn-sm" onclick="location.reload()">刷新</button>
        <?php else : ?>
        <a href="<?= h(url('/register')) ?>" class="btn btn-ghost btn-sm">返回注册</a>
        <?php endif; ?>
      </nav>
      <?php else : ?>
      <div class="topbar-actions">
        <?php require VIEWS . '/partials/weather_widget.php'; ?>
        <button type="button" class="theme-toggle" id="themeToggle" aria-label="切换日间或夜间模式" title="日间 / 夜间">
          <span class="theme-toggle-track">
            <span class="theme-toggle-thumb">
              <svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
              <svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </span>
          </span>
        </button>
        <button type="button" class="nav-menu-toggle" id="navMenuToggle" aria-expanded="false" aria-controls="siteNavPanel" aria-label="打开导航菜单">
          <span class="nav-menu-toggle-bars" aria-hidden="true">
            <span class="nav-menu-toggle-bar"></span>
            <span class="nav-menu-toggle-bar"></span>
            <span class="nav-menu-toggle-bar"></span>
          </span>
        </button>
        <div class="nav-drawer-backdrop" id="navDrawerBackdrop" aria-hidden="true"></div>
        <div class="nav-panel" id="siteNavPanel">
          <nav class="nav-links" aria-label="主导航">
            <div class="online-pill" role="status" title="近 5 分钟内有访问：已登录按账号去重，未登录按浏览器会话；登录后不会重复计算">
              <span class="online-pill-dot" aria-hidden="true"></span>
              <span class="online-pill-num"><?= (int) $onlineCount ?></span>
              <span class="online-pill-label">在线</span>
            </div>
            <a href="<?= h(url('/')) ?>" class="<?= !$isAdminSection && request_path() === '/' ? 'is-active' : '' ?>">首页</a>
            <a href="<?= h(url('/search')) ?>" class="<?= request_path() === '/search' ? 'is-active' : '' ?>">搜索</a>
            <a href="<?= h(url('/about')) ?>" class="<?= request_path() === '/about' ? 'is-active' : '' ?>">关于我们</a>
            <?php if ($u) : ?>
              <a href="<?= h(url('/topic/new')) ?>" class="<?= request_path() === '/topic/new' ? 'is-active' : '' ?>">发帖</a>
              <a href="<?= h(url('/confessions/new')) ?>" class="nav-confess-link <?= $confessWriteActive ? 'is-active' : '' ?>">表白</a>
              <a href="<?= h(url('/confessions')) ?>" class="nav-inbox-link <?= $confessInboxActive ? 'is-active' : '' ?>" title="表白收件箱">
                收件箱<?php if ($confessUnread > 0) : ?><span class="nav-inbox-badge"><?= (int) $confessUnread ?></span><?php endif; ?>
              </a>
              <a href="<?= h(url('/chat')) ?>" class="nav-inbox-link <?= $chatNavActive ? 'is-active' : '' ?>" title="私信、好友申请与未读消息">
                私信<?php if ($chatPending > 0) : ?><span class="nav-inbox-badge"><?= (int) $chatPending ?></span><?php endif; ?>
              </a>
              <a href="<?= h(url('/couple')) ?>" class="nav-inbox-link <?= $coupleNavActive ? 'is-active' : '' ?>" title="情侣空间">情侣</a>
              <a href="<?= h(url('/ask')) ?>" class="nav-inbox-link <?= $askNavActive ? 'is-active' : '' ?>" title="匿名提问箱">
                提问箱<?php if ($askUnread > 0) : ?><span class="nav-inbox-badge"><?= (int) $askUnread ?></span><?php endif; ?>
              </a>
              <a href="<?= h(url('/messages')) ?>" class="nav-inbox-link nav-messages-link <?= $messagesNavActive ? 'is-active' : '' ?>" title="<?= $messagesTableOk ? '主题回复、私信与情侣邀请' : ($coupleInvitePending > 0 || $chatUnread > 0 ? '含私信或情侣提醒；回复通知需执行 migration_topic_reply_notifications.sql' : '请先执行数据库脚本 migration_topic_reply_notifications.sql') ?>">
                消息<?php if ($messagesNavBadgeTotal > 0) : ?><span class="nav-inbox-badge nav-messages-badge"><?= (int) $messagesNavBadgeTotal ?></span><?php elseif ($u && !$messagesTableOk && $coupleInvitePending === 0) : ?><span class="nav-messages-setup">未启用</span><?php endif; ?>
              </a>
              <a href="<?= h(url('/profile')) ?>" class="nav-profile-link <?= request_path() === '/profile' ? 'is-active' : '' ?>"><?php if ($navUserLevel !== null) : ?><span class="nav-user-level" title="用户等级">Lv.<?= $navUserLevel ?></span> <?php endif; ?><?= h($u['nickname']) ?></a>
              <?php if ($u && user_can_access_admin_backend($u)) : ?>
                <a href="<?= h(url('/admin')) ?>" class="<?= $isAdminSection ? 'is-active' : '' ?>">后台</a>
              <?php endif; ?>
              <form class="inline-form nav-logout-form" method="post" action="<?= h(url('/logout')) ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <button type="submit" class="btn btn-ghost btn-sm nav-logout-btn">退出</button>
              </form>
            <?php else : ?>
              <a href="<?= h(url('/login')) ?>" class="<?= request_path() === '/login' ? 'is-active' : '' ?>">登录</a>
              <a href="<?= h(url('/register')) ?>" class="<?= (request_path() === '/register' || str_starts_with(request_path(), '/register/')) ? 'is-active' : '' ?>">注册</a>
            <?php endif; ?>
          </nav>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </header>
  <div class="shell">
    <main>
      <?php if ($flash) : ?>
        <div class="flash flash-<?= h($flash['type'] === 'success' ? 'success' : 'error') ?>">
          <?= h($flash['message']) ?>
        </div>
      <?php endif; ?>
      <?php
      $__siteAnnouncement = (!$layout_minimal && function_exists('site_announcement_for_layout'))
          ? site_announcement_for_layout()
          : null;
      ?>
      <?php if ($__siteAnnouncement !== null && $__siteAnnouncement !== '') : ?>
        <div class="site-announcement-banner" role="region" aria-label="站点公告">
          <div class="site-announcement-banner__inner">
            <span class="site-announcement-banner__label">公告</span>
            <div class="site-announcement-banner__body"><?= $__siteAnnouncement ?></div>
          </div>
        </div>
      <?php endif; ?>
      <?php
      $__shutdownAdminBanner = (!$layout_minimal && $u && function_exists('site_shutdown_effective') && function_exists('site_shutdown_user_bypasses'))
          && !empty(site_shutdown_effective()['enabled'])
          && site_shutdown_user_bypasses($u);
      ?>
      <?php if ($__shutdownAdminBanner) : ?>
        <div class="site-shutdown-admin-banner" role="status">
          <span class="site-shutdown-admin-banner__label">维护中</span>
          <span class="site-shutdown-admin-banner__text">全站维护模式已开启，普通用户无法访问；您以管理员身份可正常浏览。</span>
        </div>
      <?php endif; ?>
      <?php require VIEWS . '/' . $__view; ?>
    </main>
    <?php if (!$layout_minimal) : ?>
    <footer class="site-footer">
      <div class="site-footer-inner">
        <span class="site-footer-online">
          <span class="online-pill-dot online-pill-dot--footer" aria-hidden="true"></span>
          <strong><?= (int) $onlineCount ?></strong> 人在线
        </span>
        <span class="site-footer-divider" aria-hidden="true"></span>
        <span><?= h($appName) ?> · 文明发言，友善交流</span>
        <?php if ($icpRecord !== '') : ?>
        <span class="site-footer-divider" aria-hidden="true"></span>
        <a class="site-footer-icp" href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><?= h($icpRecord) ?></a>
        <?php endif; ?>
      </div>
    </footer>
    <?php endif; ?>
  </div>
  <?php if (function_exists('access_challenge_required') && access_challenge_required()) : ?>
    <?php require VIEWS . '/partials/access_challenge_overlay.php'; ?>
  <?php endif; ?>
  <script>
(function(){var k='luba-theme',root=document.documentElement,btn=document.getElementById('themeToggle');if(!btn)return;function sync(){var t=root.getAttribute('data-theme')||'dark';btn.setAttribute('aria-pressed',t==='light');}sync();btn.addEventListener('click',function(){var cur=root.getAttribute('data-theme')||'dark',next=cur==='light'?'dark':'light';btn.classList.add('theme-toggle-pulse');setTimeout(function(){btn.classList.remove('theme-toggle-pulse');},480);root.setAttribute('data-theme',next);try{localStorage.setItem(k,next);}catch(e){}document.body.classList.add('theme-switch-anim');clearTimeout(window._lubaTh);window._lubaTh=setTimeout(function(){document.body.classList.remove('theme-switch-anim');},620);sync();});})();
(function(){var mq=window.matchMedia('(min-width: 769px)'),toggle=document.getElementById('navMenuToggle'),panel=document.getElementById('siteNavPanel'),backdrop=document.getElementById('navDrawerBackdrop');if(!toggle||!panel)return;function navTop(){try{var inner=document.querySelector('.topbar-inner');if(inner)document.documentElement.style.setProperty('--nav-panel-top',inner.offsetHeight+'px');}catch(e){}}function close(){panel.classList.remove('is-open');toggle.setAttribute('aria-expanded','false');toggle.setAttribute('aria-label','打开导航菜单');document.body.classList.remove('nav-menu-open');if(backdrop){backdrop.classList.remove('is-visible');backdrop.setAttribute('aria-hidden','true');}}function open(){navTop();panel.classList.add('is-open');toggle.setAttribute('aria-expanded','true');toggle.setAttribute('aria-label','关闭导航菜单');document.body.classList.add('nav-menu-open');if(backdrop){backdrop.classList.add('is-visible');backdrop.setAttribute('aria-hidden','false');}}function syncMq(){if(mq.matches)close();}toggle.addEventListener('click',function(){if(panel.classList.contains('is-open'))close();else open();});if(backdrop)backdrop.addEventListener('click',close);panel.querySelectorAll('a[href]').forEach(function(a){a.addEventListener('click',function(){if(!mq.matches)close();});});panel.querySelectorAll('form').forEach(function(f){f.addEventListener('submit',function(){if(!mq.matches)close();});});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&panel.classList.contains('is-open'))close();});if(typeof mq.addEventListener==='function')mq.addEventListener('change',syncMq);else if(typeof mq.addListener==='function')mq.addListener(syncMq);window.addEventListener('resize',function(){navTop();syncMq();});navTop();})();
(function(){var msg='正在检测言论是否违规，请稍候…';document.querySelectorAll('form.js-moderation-submit').forEach(function(form){form.addEventListener('submit',function(){if(form.getAttribute('data-moderation-sent')==='1')return;form.setAttribute('data-moderation-sent','1');form.querySelectorAll('button[type="submit"]').forEach(function(btn){btn.disabled=true;btn.textContent=msg;});});});})();
(function(){
document.querySelectorAll('[data-cos-upload-wrap]').forEach(function(wrap){
  if(wrap.getAttribute('data-cos-ready')!=='1')return;
  var uploadUrl=wrap.getAttribute('data-upload-url');
  var csrf=wrap.getAttribute('data-csrf');
  var input=wrap.querySelector('.cos-forum-file-input');
  var st=wrap.querySelector('.cos-forum-upload-status');
  var hid=wrap.querySelector('.js-cos-image-urls');
  var listBox=wrap.querySelector('.cos-forum-preview-list');
  var itemsEl=wrap.querySelector('.cos-forum-preview-items');
  if(!uploadUrl||!csrf||!input||input.disabled||!hid||!itemsEl)return;
  var cosMax=12;
  var maxAttr=wrap.getAttribute('data-cos-max');
  if(maxAttr!==null&&maxAttr!==''){var pm=parseInt(maxAttr,10);if(!isNaN(pm)&&pm>0)cosMax=pm;}
  function getUrls(){
    try{var a=JSON.parse(hid.value||'[]');return Array.isArray(a)?a:[];}catch(e){return[];}
  }
  function setUrls(arr){hid.value=JSON.stringify(arr);syncUi();}
  function syncUi(){
    var arr=getUrls();
    itemsEl.innerHTML='';
    if(arr.length===0){if(listBox)listBox.hidden=true;return;}
    if(listBox)listBox.hidden=false;
    arr.forEach(function(url){
      var item=document.createElement('div');
      item.className='cos-forum-preview-item';
      var img=document.createElement('img');
      img.src=url;img.alt='';img.className='cos-forum-preview-thumb';img.loading='lazy';
      var rm=document.createElement('button');
      rm.type='button';rm.className='cos-forum-preview-remove';rm.setAttribute('aria-label','移除');rm.textContent='×';
      rm.addEventListener('click',function(){
        setUrls(getUrls().filter(function(x){return x!==url;}));
      });
      item.appendChild(img);item.appendChild(rm);itemsEl.appendChild(item);
    });
  }
  input.addEventListener('change',function(){
    if(!input.files||!input.files[0])return;
    var file=input.files[0];
    input.value='';
    if(st)st.textContent='上传中…';
    var fd=new FormData();
    fd.append('_csrf',csrf);
    fd.append('image',file);
    fetch(uploadUrl,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json().catch(function(){return{ok:false,error:'响应无效'}});}).then(function(j){
      if(j&&j.ok&&j.url){
        var u=getUrls();
        if(u.length>=cosMax){if(st)st.textContent='已达 '+cosMax+' 张上限';return;}
        u.push(j.url);setUrls(u);
        if(st)st.textContent='已添加图片';
      }else{
        if(st)st.textContent=(j&&j.error)?j.error:'上传失败';
      }
    }).catch(function(){if(st)st.textContent='网络错误';});
  });
  syncUi();
});
})();
(function(){
document.querySelectorAll('form.couple-album-form').forEach(function(form){
  form.addEventListener('submit',function(){
    var mirror=document.getElementById('couple_album_cos_mirror');
    if(!mirror)return;
    var wrap=form.querySelector('[data-cos-upload-wrap]');
    if(!wrap)return;
    var hid=wrap.querySelector('.js-cos-image-urls');
    if(!hid)return;
    try{
      var a=JSON.parse(hid.value||'[]');
      if(Array.isArray(a)){
        for(var i=0;i<a.length;i++){
          if(typeof a[i]==='string'&&a[i].trim()!==''){mirror.value=a[i].trim();return;}
        }
      }
    }catch(e){}
  });
});
})();
(function(){
document.querySelectorAll('[data-cos-video-wrap]').forEach(function(wrap){
  if(wrap.getAttribute('data-cos-ready')!=='1')return;
  var uploadUrl=wrap.getAttribute('data-upload-url');
  var csrf=wrap.getAttribute('data-csrf');
  var input=wrap.querySelector('.cos-forum-video-input');
  var st=wrap.querySelector('.cos-forum-video-status');
  var hid=wrap.querySelector('.js-cos-video-urls');
  var listBox=wrap.querySelector('.cos-forum-video-preview-list');
  var itemsEl=wrap.querySelector('.cos-forum-video-preview-items');
  if(!uploadUrl||!csrf||!input||input.disabled||!hid||!itemsEl)return;
  function getUrls(){
    try{var a=JSON.parse(hid.value||'[]');return Array.isArray(a)?a:[];}catch(e){return[];}
  }
  function setUrls(arr){hid.value=JSON.stringify(arr);syncUi();}
  function syncUi(){
    var arr=getUrls();
    itemsEl.innerHTML='';
    if(arr.length===0){if(listBox)listBox.hidden=true;return;}
    if(listBox)listBox.hidden=false;
    arr.forEach(function(url){
      var item=document.createElement('div');
      item.className='cos-forum-preview-item cos-forum-preview-item--video';
      var vid=document.createElement('video');
      vid.src=url;vid.muted=true;vid.playsInline=true;vid.className='cos-forum-preview-video';vid.setAttribute('preload','metadata');
      var rm=document.createElement('button');
      rm.type='button';rm.className='cos-forum-preview-remove';rm.setAttribute('aria-label','移除');rm.textContent='×';
      rm.addEventListener('click',function(){setUrls(getUrls().filter(function(x){return x!==url;}));});
      item.appendChild(vid);item.appendChild(rm);itemsEl.appendChild(item);
    });
  }
  input.addEventListener('change',function(){
    if(!input.files||!input.files[0])return;
    var file=input.files[0];
    input.value='';
    if(st)st.textContent='上传视频中，请稍候…';
    var fd=new FormData();
    fd.append('_csrf',csrf);
    fd.append('video',file);
    fetch(uploadUrl,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json().catch(function(){return{ok:false,error:'响应无效'}});}).then(function(j){
      if(j&&j.ok&&j.url){
        var u=getUrls();
        if(u.length>=3){if(st)st.textContent='最多 3 个视频';return;}
        u.push(j.url);setUrls(u);
        if(st)st.textContent='已添加视频';
      }else{
        if(st)st.textContent=(j&&j.error)?j.error:'上传失败';
      }
    }).catch(function(){if(st)st.textContent='网络错误';});
  });
  syncUi();
});
})();
<?php if ((!empty($checkinCelebration) && is_array($checkinCelebration)) || (!empty($levelUpOverlay) && is_array($levelUpOverlay))) : ?>
(function(){
  function playLevelUp(from,to){
    var ov=document.createElement('div');
    ov.className='level-up-overlay';
    ov.setAttribute('role','dialog');
    ov.setAttribute('aria-modal','true');
    ov.innerHTML='<div class="level-up-backdrop"></div><div class="level-up-card"><div class="level-up-glow"></div><p class="level-up-kicker">恭喜升级</p><p class="level-up-title">Lv.'+from+' <span class="level-up-arrow">→</span> Lv.'+to+'</p><p class="level-up-sub">继续签到、发帖解锁更高等级</p></div>';
    document.body.appendChild(ov);
    requestAnimationFrame(function(){ov.classList.add('is-visible');});
    var n=48,i,a;
    for(i=0;i<n;i++){
      a=document.createElement('span');
      a.className='level-up-particle';
      a.style.setProperty('--d',(1.8+Math.random()*1.6)+'s');
      a.style.setProperty('--x',(Math.random()*200-100)+'px');
      a.style.setProperty('--r',(Math.random()*360)+'deg');
      a.style.left=(50+Math.random()*40-20)+'%';
      a.style.top=(40+Math.random()*25)+'%';
      ov.appendChild(a);
    }
    setTimeout(function(){ov.classList.add('is-exit');},3200);
    setTimeout(function(){if(ov.parentNode)ov.parentNode.removeChild(ov);},4000);
  }
  function playCheckin(xp){
    var ov=document.createElement('div');
    ov.className='checkin-celebration-overlay';
    ov.setAttribute('role','dialog');
    ov.setAttribute('aria-modal','true');
    ov.innerHTML='<div class="checkin-celebration-backdrop"></div><div class="checkin-celebration-card"><div class="checkin-celebration-glow"></div><div class="checkin-celebration-icon" aria-hidden="true">✓</div><p class="checkin-celebration-kicker">签到成功</p><p class="checkin-celebration-title">+'+xp+' 经验</p><p class="checkin-celebration-sub">明天再来，坚持升级</p></div>';
    document.body.appendChild(ov);
    requestAnimationFrame(function(){ov.classList.add('is-visible');});
    var n=40,i,a,ang,dist;
    for(i=0;i<n;i++){
      ang=Math.random()*6.28318;
      dist=80+Math.random()*140;
      a=document.createElement('span');
      a.className='checkin-celebration-particle';
      a.style.setProperty('--d',(1.4+Math.random()*1.2)+'s');
      a.style.setProperty('--x',(Math.cos(ang)*dist)+'px');
      a.style.setProperty('--y',(Math.sin(ang)*dist)+'px');
      a.style.setProperty('--r',(Math.random()*360)+'deg');
      a.style.left='50%';
      a.style.top='42%';
      ov.appendChild(a);
    }
    setTimeout(function(){ov.classList.add('is-exit');},2800);
    setTimeout(function(){if(ov.parentNode)ov.parentNode.removeChild(ov);},3600);
  }
  var lu=<?= (!empty($levelUpOverlay) && is_array($levelUpOverlay)) ? json_encode(['from' => (int) $levelUpOverlay['from'], 'to' => (int) $levelUpOverlay['to']]) : 'null' ?>;
  var ck=<?= (!empty($checkinCelebration) && is_array($checkinCelebration)) ? json_encode(['xp' => (int) $checkinCelebration['xp']]) : 'null' ?>;
  if(ck){
    playCheckin(ck.xp);
    if(lu)setTimeout(function(){playLevelUp(lu.from,lu.to);},3000);
  }else if(lu){
    playLevelUp(lu.from,lu.to);
  }
})();
<?php endif; ?>
  </script>
</body>
</html>
