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
        // 站点 Logo，相对 public/，如 assets/images/logo.png；留空则自动查找 public/assets/images/logo.* 或 public/logo.*
        'logo_file' => '',
        // 已废弃：发帖与回复必须登录。保留键仅为兼容旧配置。
        'allow_guest_topics' => false,
    ],
    /**
     * Spug 短信推送：GET 完整 URL（含路径中的密钥），系统会追加 ?to=&name=&code=&number=
     * code 须为 4–6 位数字（本系统使用 6 位）。注册与忘记密码共用此地址与防刷（sms_send_log）。
     * @see https://push.spug.cc/
     */
    'sms' => [
        'spug_sms_url' => 'https://push.spug.cc/sms/你的推送密钥',
        'sms_name' => '',
        'sms_number' => '10',
        'enabled' => true,
    ],
    /**
     * DeepSeek 内容审核：发帖 / 回复前调用 Chat Completions（勿将真实密钥提交到公开仓库）。
     * @see https://api-docs.deepseek.com/
     */
    'moderation' => [
        'enabled' => false,
        'api_key' => '',
        'api_url' => 'https://api.deepseek.com/v1/chat/completions',
        'model' => 'deepseek-chat',
        'timeout' => 35,
        // API 故障或返回无法解析时是否仍允许发帖（true=放行，false=拦截）
        'fail_open' => true,
    ],
    /** 登录会话：记住登录时的 Cookie 天数；session_gc_maxlifetime 须不小于 remember_days 换算的秒数 */
    'auth' => [
        'remember_days' => 30,
        'session_gc_maxlifetime' => 2592000,
    ],
];
