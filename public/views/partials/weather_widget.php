<?php
declare(strict_types=1);
$weatherApiUrl = 'https://uapis.cn/api/v1/misc/weather';
?>
<div id="weatherWidget" class="weather-widget weather-widget--loading" role="status" aria-live="polite" hidden>
  <div class="weather-widget__glyph" aria-hidden="true"></div>
  <div class="weather-widget__body">
    <span class="weather-widget__temp" id="weatherTemp">—</span>
    <span class="weather-widget__desc" id="weatherDesc"></span>
  </div>
  <div class="weather-widget__meta muted" id="weatherMeta"></div>
</div>
<script>
(function(){
  var root = document.getElementById('weatherWidget');
  if (!root) return;
  var elTemp = document.getElementById('weatherTemp');
  var elDesc = document.getElementById('weatherDesc');
  var elMeta = document.getElementById('weatherMeta');
  var API = <?= json_encode($weatherApiUrl, JSON_UNESCAPED_SLASHES) ?>;

  function mapVariant(weather) {
    var s = String(weather || '');
    if (/雪|霜|冰雹|冰粒/.test(s)) return 'snow';
    if (/雷|雨|阵雨|毛毛雨|冻雨/.test(s)) return 'rain';
    if (/雾/.test(s)) return 'fog';
    if (/霾/.test(s)) return 'haze';
    if (/沙|尘/.test(s)) return 'sand';
    if (/风/.test(s)) return 'windy';
    if (/晴/.test(s)) return 'sunny';
    if (/云|阴/.test(s)) return 'cloudy';
    return 'default';
  }

  function locLine(d) {
    var a = [];
    if (d.district) a.push(d.district);
    else if (d.city) a.push(d.city);
    if (d.province && a[0] && String(d.province).indexOf(String(a[0])) < 0) {
      a.push(d.province);
    }
    return a.join(' · ');
  }

  root.hidden = false;
  fetch(API, { mode: 'cors', cache: 'no-store' })
    .then(function(r) { return r.ok ? r.json() : null; })
    .then(function(d) {
      if (!d || d.temperature === undefined || d.temperature === null) {
        root.hidden = true;
        return;
      }
      var w = String(d.weather || '天气');
      var v = mapVariant(w);
      root.className = 'weather-widget weather-widget--' + v;
      elTemp.textContent = String(d.temperature) + '°';
      elDesc.textContent = w;
      var meta = [];
      var loc = locLine(d);
      if (loc) meta.push(loc);
      if (d.wind_direction || d.wind_power) {
        meta.push([d.wind_direction, d.wind_power].filter(Boolean).join(' '));
      }
      if (d.humidity != null && d.humidity !== '') meta.push('湿度 ' + d.humidity + '%');
      elMeta.textContent = meta.join(' · ');
      var tip = [w, String(d.temperature) + '°', loc, d.report_time].filter(Boolean).join(' · ');
      root.setAttribute('title', tip);
    })
    .catch(function() {
      root.hidden = true;
    });
})();
</script>
