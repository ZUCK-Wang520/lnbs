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
        // 工信部 ICP 备案号（完整字符串）；留空则页脚不显示备案链接
        'icp_record' => '',
        // 网站根目录指向 public/（如 lnbs.fun 站点目录设为 public）时留空。
        // 若必须用 http://域名/public/index.php 访问，则填 '/public'。
        'base_url' => '',
        // 一般留空；仅当 CSS 仍 404 时手动指定，例如 '/public' 或子目录 '/forum'
        'asset_base' => '',
        // 宝塔 Nginx 未配伪静态时 /login 会 404：设为 true，链接变为 index.php?r=/login
        'router_query' => false,
        // 强制 HTTPS：开启后，所有 http 请求会 302 跳转到 https，并开启 HSTS（要求你已正确配置 https 证书）
        'force_https' => false,
        // 站点 Logo（可选）：相对 public/ 的静态路径，如 assets/images/logo.png。
        // 推荐：登录后台「站点 Logo」上传，无需改此配置；留空且未上传时顶栏仅显示站名。
        'logo_file' => '',
        // 首页「当周热门榜」自然周：周一至周日按此时区划分（PHP DateTimeZone 合法标识，如 Asia/Shanghai）
        'timezone' => 'Asia/Shanghai',
        // 热门榜快照自动刷新间隔（秒），过期后下次打开首页会重算本周浏览/点赞；15–3600，默认 120
        'home_hot_board_snapshot_ttl_seconds' => 120,
        // 已废弃：发帖与回复必须登录。保留键仅为兼容旧配置。
        'allow_guest_topics' => false,
        /**
         * 站点维护（备用）：执行 migration_site_shutdown.sql 后，请在管理后台「全站维护模式」中配置。
         * 未迁移数据库时，可临时在此开启；管理员（站长与二级管理员）仍可正常访问全站。
         */
        'shutdown' => [
            'enabled' => false,
            'message' => "本站正在进行系统维护与功能升级。\n期间普通用户无法访问，敬请谅解。",
            'eta' => '',
        ],
    ],
    /**
     * 首页「站点更新」
     * - source=local：服务器执行 git log（需 git、shell_exec；私人仓库无意义）
     * - source=github：调用 GitHub REST API（推荐；私人仓库须在 github_token 填 PAT，勿提交仓库）
     */
    'git_updates' => [
        'enabled' => true,
        // local | github
        'source' => 'local',
        'limit' => 12,
        'cache_ttl_seconds' => 300,
        'repo_path' => '',
        'github_owner' => '',
        'github_repo' => '',
        // 私人仓库必填：GitHub → Settings → Developer settings → Personal access tokens（勾选 repo 读权限）
        'github_token' => '',
        // 留空时：github 源自动为 https://github.com/owner/repo/commit
        'commit_base_url' => '',
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
        // strict / normal / lenient：越宽松越少误拦截
        'strictness' => 'normal',
    ],
    /** 登录会话：记住登录时的 Cookie 天数；session_gc_maxlifetime 须不小于 remember_days 换算的秒数 */
    'auth' => [
        'remember_days' => 30,
        'session_gc_maxlifetime' => 2592000,
        /**
         * 登录表单 RSA 加密（前端 WebCrypto + 后端 openssl 解密）。
         * 注意：这不是 HTTPS 的替代品；仍强烈建议开启 app.force_https。
         */
        'login_rsa' => [
            'enabled' => false,
            // 设为 true 后，后端将拒绝明文 password 提交（必须提交 password_rsa）
            'require' => false,
            // PEM 格式私钥（-----BEGIN PRIVATE KEY----- ...）。仅放在 config.local.php，勿提交仓库
            'private_key_pem' => '',
            // 可留空：系统会从私钥推导公钥；若填写必须为 PEM 格式公钥
            'public_key_pem' => '',
        ],
    ],
    /**
     * GeeTest 行为验证第三代（Sensebot）。
     * 文档：https://docs.geetest.com/sensebot/overview/guide/operate
     */
    'geetest' => [
        'enabled' => false,
        'id' => '',
        'key' => '',
        // md5 / sha256 / hmac-sha256
        'digestmod' => 'md5',
    ],
    /**
     * 访问异常时强制全站滑块（依赖 geetest.enabled）。
     * 触发：同一会话连续登录失败达阈值、或触发防刷（回复过快等）。
     */
    'access_challenge' => [
        'enabled' => true,
        // 连续密码错误次数达到后要求验证（2–20）
        'login_fail_threshold' => 3,
        // 标记有效期（秒），验证通过即清除
        'ttl_seconds' => 7200,
        // 因 anti_spam 拦截回复时是否同时要求滑块
        'mark_on_anti_spam_block' => true,
    ],
    /**
     * 请求防火墙：识别恶意扫描、SQL 注入、XSS 等，自动封禁 IP 并展示拦截页。
     * 封禁记录写入 ip_bans（reason 以 [防火墙] 开头），全站拦截；管理员登录后台可豁免检测。
     * ban_hours=0 表示不限期，直至后台「解除 IP 封禁」。
     */
    'firewall' => [
        'enabled' => true,
        'ban_hours' => 0,
        'max_field_bytes' => 65536,
        // 永不拦截的 IP（如办公室出口），按需填写
        'whitelist_ips' => [],
    ],
    /**
     * 防刷（主要针对回复/评论）。
     */
    'anti_spam' => [
        'enabled' => true,
        // 单账号 1 分钟内最多回复次数
        'reply_per_minute' => 8,
        // 单账号 10 分钟内最多回复次数
        'reply_per_10min' => 40,
        // 同一账号在该秒数内重复提交同样内容将被拦截
        'duplicate_window_sec' => 25,
    ],
    /**
     * 实名认证（二要素：姓名 + 身份证号，阿里云市场 APPCODE）。
     * 管理员在后台「用户」中可为指定用户开启资格；用户仅在有资格时可在个人中心提交。
     * 密钥仅写入 config.local.php，勿提交仓库。
     */
    'realname' => [
        'enabled' => false,
        'appcode' => '',
        'api_url' => 'https://zidv2.market.alicloudapi.com/idcheck/Post',
        'timeout' => 60,
        // 实名信息加密存储密钥（32+ 字符随机串），仅写入 config.local.php，勿提交仓库
        'storage_key' => '',
    ],
    /**
     * 腾讯云 COS：发帖/回复插图（PHP SDK 服务端上传）。
     * 部署：在 public/ 目录执行 composer install（安装 qcloud/cos-sdk-v5）。
     * 桶为「公有读写」时，访客一般可直接打开 public_base_url 下的图片；仍建议勿将密钥提交到 Git。
     *
     * @see https://cloud.tencent.com/document/product/436/64283
     */
    'cos' => [
        'enabled' => false,
        // CAM 访问密钥：https://console.cloud.tencent.com/cam/capi
        // 真实密钥只写在 config.local.php，切勿提交到公开仓库
        'secret_id' => '',
        'secret_key' => '',
        // 与控制台「所属地域」一致，例如 ap-hongkong、ap-guangzhou
        'region' => 'ap-hongkong',
        // 控制台「存储桶名称」全文（示例：mybucket-1250000000）
        'bucket' => '',
        // 「请求域名」加 https，无尾部斜杠
        'public_base_url' => '',
        'key_prefix' => 'forum',
        // 公有桶可填 public-read；若控制台关闭对象 ACL 则留空
        'object_acl' => 'public-read',
        'max_bytes' => 5242880,
        // 发帖视频（MP4/WebM/MOV），默认 80MB；需同时调大 PHP upload_max_filesize / post_max_size
        'max_video_bytes' => 83886080,
        // 绑定 CDN 后把 CDN 根 URL 写在这里（可多个），旧帖 COS 直链才仍能显示为图片
        'extra_allowed_url_prefixes' => [],
    ],
];
