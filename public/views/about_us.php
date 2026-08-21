<?php declare(strict_types=1); ?>
<nav class="breadcrumb">
  <a href="<?= h(url('/')) ?>">首页</a>
  <span> / </span>
  <span>关于我们</span>
</nav>

<article class="card" style="max-width:42rem;margin:0 auto;">
  <h1 style="margin-top:0;font-size:1.35rem;">关于我们</h1>
  <p class="muted" style="margin-top:0;line-height:1.8;">
    欢迎来到 <?= h($appName ?? '鲁巴校园论坛') ?>。我们致力于为同学们提供一个文明、友善、可持续的交流空间，
    支持发帖、讨论与互助，同时通过内容治理与社区规则维护良好的学习氛围。
  </p>

  <h2 style="margin-top:1.25rem;font-size:1.05rem;">我们的目标</h2>
  <ul style="line-height:1.75;margin:0.75rem 0;padding-left:1.25rem;">
    <li style="margin-bottom:0.6rem;">让校园交流更安全、更有秩序。</li>
    <li style="margin-bottom:0.6rem;">鼓励理性表达与友善讨论。</li>
    <li>持续优化体验，让每一次发言都更轻松。</li>
  </ul>

  <h2 style="margin-top:1.25rem;font-size:1.05rem;">社区规则</h2>
  <p style="line-height:1.8;margin:0.75rem 0;">
    我们要求所有用户遵守法律法规与学校相关规定，尊重他人、保护隐私、拒绝违规内容。
    具体要求可参考《用户须知》。
  </p>

  <p class="muted" style="margin-bottom:0;font-size:0.9rem;line-height:1.8;">
    如果你有建议或反馈，欢迎通过站内相关渠道联系管理人员。
  </p>
</article>

