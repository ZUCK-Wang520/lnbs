<?php

declare(strict_types=1);

/**
 * 未配置伪静态时访问 /admin/ip-bans（无尾斜杠）的入口。
 */
$_GET['r'] = '/admin/ip-bans';
require dirname(__DIR__) . '/index.php';
