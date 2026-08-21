<?php

declare(strict_types=1);

/**
 * 复制为 config.local.php 并填写 MySQL 连接信息。
 */
return [
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=luba_forum;charset=utf8mb4',
        'user' => 'root',
        'pass' => '',
    ],
    'app' => [
        'name' => '鲁巴校园论坛',
        // 网站根目录指向 public/（如 lnbs.fun 站点目录设为 public）时留空。
        // 若必须用 http://域名/public/index.php 访问，则填 '/public'。
        'base_url' => '',
        // 一般留空；仅当 CSS 仍 404 时手动指定，例如 '/public' 或子目录 '/forum'
        'asset_base' => '',
        // 宝塔 Nginx 未配伪静态时 /login 会 404：设为 true，链接变为 index.php?r=/login
        'router_query' => false,
    ],
];
