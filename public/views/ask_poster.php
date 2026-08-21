<?php declare(strict_types=1);
$appName = (string) ($GLOBALS['APP_CONFIG']['app']['name'] ?? '校园论坛');
?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <a href="<?= h(url('/ask')) ?>">匿名提问箱</a>
  <span> / </span>
  <a href="<?= h(url('/ask/box/' . (int) $box['id'])) ?>"><?= h((string) $box['title']) ?></a>
  <span> / </span>
  <span>分享海报</span>
</nav>

<div class="ask-poster-wrap">
  <div class="ask-poster-stage">
    <canvas id="askPosterCanvas" class="ask-poster-canvas" width="1080" height="1620" role="img" aria-label="匿名提问二维码海报"></canvas>
    <div class="ask-poster-loading" id="askPosterLoading">海报生成中…</div>
  </div>

  <div class="ask-poster-side">
    <h1 class="ask-section-title" style="margin-top:0;">分享二维码海报</h1>
    <p class="muted">把海报保存下来，发到朋友圈、群聊或打印张贴。任何人扫码登录后即可向你匿名提问。</p>

    <div class="ask-share-row" style="margin:1rem 0;">
      <input id="askPosterUrl" class="ask-share-input" type="text" readonly value="<?= h($shareUrl) ?>">
      <button type="button" class="btn btn-ghost btn-sm" id="askPosterCopyBtn">复制链接</button>
    </div>

    <div class="ask-poster-actions">
      <button type="button" class="btn btn-primary" id="askPosterDownloadBtn">下载海报</button>
      <a class="btn btn-ghost" href="<?= h($shareUrl) ?>" target="_blank" rel="noopener">预览提问页</a>
      <a class="btn btn-ghost" href="<?= h(url('/ask/box/' . (int) $box['id'])) ?>">返回管理</a>
    </div>
    <p class="muted" style="font-size:0.82rem;margin-top:1rem;">提示：手机端可长按海报图片保存到相册。</p>
  </div>
</div>

<div id="askQrHolder" style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true"></div>

<script src="<?= h(asset('qrcode.min.js')) ?>"></script>
<script>
(function(){
  var SHARE_URL=<?= json_encode($shareUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var APP_NAME=<?= json_encode($appName, JSON_UNESCAPED_UNICODE) ?>;
  var BOX_TITLE=<?= json_encode((string) $box['title'], JSON_UNESCAPED_UNICODE) ?>;
  var OWNER=<?= json_encode('向 ' . $ownerName . ' 匿名提问', JSON_UNESCAPED_UNICODE) ?>;
  var LOGO_URL=<?= json_encode((string) ($logoUrl ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  var canvas=document.getElementById('askPosterCanvas');
  var loading=document.getElementById('askPosterLoading');
  if(!canvas||!canvas.getContext)return;
  var ctx=canvas.getContext('2d');
  var W=canvas.width,H=canvas.height;

  function roundRect(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath();}

  function wrapText(c,text,maxWidth){
    var chars=text.split('');
    var lines=[],line='';
    for(var i=0;i<chars.length;i++){
      var test=line+chars[i];
      if(c.measureText(test).width>maxWidth&&line!==''){lines.push(line);line=chars[i];}
      else{line=test;}
    }
    if(line!=='')lines.push(line);
    return lines;
  }

  // 在二维码中心叠加 LOGO（白色圆角底 + contain 缩放，避免遮挡过多码点）
  function drawLogoOnQr(qrX,qrY,qrSize,logoImg){
    if(!logoImg)return;
    var iw=logoImg.naturalWidth||logoImg.width,ih=logoImg.naturalHeight||logoImg.height;
    if(!iw||!ih)return;
    var box=qrSize*0.22;
    var lx=qrX+(qrSize-box)/2,ly=qrY+(qrSize-box)/2;
    var pad=box*0.16;
    // 白色圆角底板 + 轻描边
    ctx.save();
    ctx.shadowColor='rgba(20,20,60,0.18)';
    ctx.shadowBlur=18;
    ctx.fillStyle='#ffffff';
    roundRect(ctx,lx-pad,ly-pad,box+pad*2,box+pad*2,(box+pad*2)*0.28);
    ctx.fill();
    ctx.restore();
    // contain 缩放并圆角裁剪
    var scale=Math.min(box/iw,box/ih);
    var dw=iw*scale,dh=ih*scale;
    var dx=lx+(box-dw)/2,dy=ly+(box-dh)/2;
    ctx.save();
    roundRect(ctx,lx,ly,box,box,box*0.22);
    ctx.clip();
    ctx.drawImage(logoImg,dx,dy,dw,dh);
    ctx.restore();
  }

  function drawPoster(qrCanvas,logoImg){
    // 背景渐变
    var g=ctx.createLinearGradient(0,0,W,H);
    g.addColorStop(0,'#7c6cf8');
    g.addColorStop(0.55,'#6a7bf7');
    g.addColorStop(1,'#4fd1c5');
    ctx.fillStyle=g;
    ctx.fillRect(0,0,W,H);
    // 柔光装饰
    var rg=ctx.createRadialGradient(W*0.8,H*0.12,40,W*0.8,H*0.12,W*0.7);
    rg.addColorStop(0,'rgba(255,255,255,0.28)');
    rg.addColorStop(1,'rgba(255,255,255,0)');
    ctx.fillStyle=rg;ctx.fillRect(0,0,W,H);

    ctx.textAlign='center';

    // 顶部品牌
    ctx.fillStyle='rgba(255,255,255,0.92)';
    ctx.font='600 40px "Noto Sans SC","DM Sans",system-ui,sans-serif';
    ctx.fillText(APP_NAME,W/2,150);

    // kicker 胶囊
    var kicker='匿名提问箱';
    ctx.font='500 34px "Noto Sans SC",system-ui,sans-serif';
    var kw=ctx.measureText(kicker).width+64;
    ctx.fillStyle='rgba(255,255,255,0.18)';
    roundRect(ctx,W/2-kw/2,200,kw,68,34);ctx.fill();
    ctx.fillStyle='#ffffff';
    ctx.fillText(kicker,W/2,246);

    // 标题（自动换行，最多 3 行）
    ctx.fillStyle='#ffffff';
    ctx.font='700 76px "Noto Sans SC",system-ui,sans-serif';
    var lines=wrapText(ctx,BOX_TITLE,W-200).slice(0,3);
    var ty=380;
    for(var i=0;i<lines.length;i++){ctx.fillText(lines[i],W/2,ty);ty+=92;}

    // 箱主
    ctx.fillStyle='rgba(255,255,255,0.9)';
    ctx.font='400 42px "Noto Sans SC",system-ui,sans-serif';
    ctx.fillText(OWNER,W/2,ty+20);

    // 白色卡片 + 二维码
    var cardW=760,cardH=900;
    var cardX=(W-cardW)/2,cardY=H-cardH-90;
    ctx.save();
    ctx.shadowColor='rgba(20,20,60,0.28)';
    ctx.shadowBlur=60;ctx.shadowOffsetY=24;
    ctx.fillStyle='#ffffff';
    roundRect(ctx,cardX,cardY,cardW,cardH,56);ctx.fill();
    ctx.restore();

    // 二维码居中
    var qrSize=600;
    var qrX=(W-qrSize)/2,qrY=cardY+70;
    if(qrCanvas){
      ctx.drawImage(qrCanvas,qrX,qrY,qrSize,qrSize);
      drawLogoOnQr(qrX,qrY,qrSize,logoImg);
    }

    // 卡片文案
    ctx.fillStyle='#0c0f18';
    ctx.font='700 52px "Noto Sans SC",system-ui,sans-serif';
    ctx.fillText('扫码，匿名向我提问',W/2,qrY+qrSize+110);
    ctx.fillStyle='#6b7280';
    ctx.font='400 34px "Noto Sans SC",system-ui,sans-serif';
    ctx.fillText('完全匿名 · 对方看不到你是谁',W/2,qrY+qrSize+168);

    // 底部链接
    ctx.fillStyle='rgba(255,255,255,0.85)';
    ctx.font='400 30px "DM Sans",system-ui,sans-serif';
    ctx.fillText(SHARE_URL,W/2,H-40);

    if(loading)loading.style.display='none';
  }

  // 生成二维码（先加载 LOGO，再合成，保证中心叠加成功）
  function buildPoster(logoImg){
    try{
      var holder=document.getElementById('askQrHolder');
      /* global QRCode */
      new QRCode(holder,{text:SHARE_URL,width:600,height:600,correctLevel:QRCode.CorrectLevel.H,colorDark:'#0c0f18',colorLight:'#ffffff'});
      var qrCanvas=holder.querySelector('canvas');
      drawPoster(qrCanvas||null,logoImg||null);
    }catch(e){drawPoster(null,logoImg||null);}
  }

  if(LOGO_URL){
    var logo=new Image();
    // 同源图片不会污染画布；跨域 CDN 若无 CORS 头会走 onerror，此时跳过 LOGO 但海报仍可下载
    logo.crossOrigin='anonymous';
    logo.onload=function(){buildPoster(logo);};
    logo.onerror=function(){buildPoster(null);};
    logo.src=LOGO_URL;
  }else{
    buildPoster(null);
  }

  var dl=document.getElementById('askPosterDownloadBtn');
  if(dl){dl.addEventListener('click',function(){
    try{
      var url=canvas.toDataURL('image/png');
      var a=document.createElement('a');
      a.href=url;a.download='提问箱海报.png';
      document.body.appendChild(a);a.click();document.body.removeChild(a);
    }catch(e){alert('下载失败，请尝试长按海报保存图片。');}
  });}

  var cp=document.getElementById('askPosterCopyBtn');
  if(cp){cp.addEventListener('click',function(){
    var inp=document.getElementById('askPosterUrl');
    if(!inp)return;
    function done(){var t=cp.textContent;cp.textContent='已复制';setTimeout(function(){cp.textContent=t;},1500);}
    if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(inp.value).then(done,function(){inp.select();try{document.execCommand('copy');done();}catch(e){}});}
    else{inp.select();try{document.execCommand('copy');done();}catch(e){}}
  });}
})();
</script>
