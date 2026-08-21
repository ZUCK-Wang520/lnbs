# 鲁巴校园论坛（LNBS）

[![License: PolyForm Noncommercial 1.0.0](https://img.shields.io/badge/license-PolyForm%20Noncommercial%201.0.0-blue.svg)](./LICENSE)

面向校园场景的轻量社区系统：**纯 PHP + MySQL**，无前端构建步骤。推荐把 Web 根目录指向仓库里的 `public/`，便于宝塔 / 虚拟主机部署。

---

## 功能一览

| 类别 | 能力 |
|------|------|
| 论坛 | 版块浏览、发帖/盖楼、匿名发帖、主题点赞、回复通知 |
| 媒体 | 图片 / 视频附件（腾讯云 COS，可选） |
| 社交 | 私信与好友、表白墙、签到与等级经验（可选） |
| 安全 | GeeTest 行为验证、登录 RSA、访问异常滑块、请求防火墙、防刷 |
| 账号 | 短信注册 / 找回密码（Spug 等）、实名认证（可选） |
| 审核 | DeepSeek 内容审核 + 管理端人工复核（可选） |
| 运营 | 版块与用户管理、禁言 / 登录封禁 / IP 封禁、全站公告、审计日志、维护模式 |
| 体验 | 日间 / 夜间主题、移动端导航、顶栏天气（第三方，可选） |

---

## 许可证（禁止商用）

本项目采用 **[PolyForm Noncommercial License 1.0.0](./LICENSE)**：

- **允许**：个人学习、研究、业余爱好、教育与公益等非商业用途  
- **禁止**：用于商业目的或获取商业利益  
- 分发与修改须保留许可证与版权声明  

完整条款见 [`LICENSE`](./LICENSE)。

---

## 环境要求

| 项目 | 要求 |
|------|------|
| PHP | 8.1+（扩展：`pdo_mysql`、`mbstring`、`session`；启用 COS 时需 `curl` / Composer） |
| 数据库 | MySQL 5.7+ 或 8.x |
| Web 服务器 | Nginx / Apache / PHP 内置服务器（开发用） |

---

## 快速安装

在仓库根目录操作。以下路径均相对于仓库根。

### 1. 导入数据库

任选其一：

```bash
# 新建库名为 luba_forum 时
mysql -u root -p < public/database/schema.sql

# 面板已建好库（例如库名、用户均为 lnbs）时
mysql -u lnbs -p lnbs < public/database/schema_lnbs.sql
```

也可在 phpMyAdmin 中导入对应 SQL 文件。

### 2. 创建本地配置

```bash
cp public/config/config.example.php public/config/config.local.php
```

编辑 `public/config/config.local.php`，至少填写：

- `db.dsn` / `db.user` / `db.pass`：数据库连接  
- 其余短信、COS、GeeTest 等按需填写；不需要的功能保持 `enabled => false` 即可  

**切勿**把 `config.local.php` 提交到 Git（已在 `.gitignore` 中忽略）。

### 3. 写入初始数据

```bash
php public/database/seed.php
```

| 账号 | 密码 | 说明 |
|------|------|------|
| `admin@luba.local` | `admin123` | 默认管理员，**登录后请立即修改** |

种子脚本还会创建匿名发帖占位账号。

### 4. 旧库升级（仅已有数据库时）

若 `topics` / `posts` 还没有 `is_anonymous`、`anon_nickname` 等字段，先执行迁移，再跑 seed：

```bash
mysql -u 用户 -p 数据库名 < public/database/migration_anonymous.sql
php public/database/seed.php
```

其他功能迁移 SQL 见 `public/database/migration_*.sql`，按需执行。

---

## 运行方式

### 开发：PHP 内置服务器

```bash
cd public
php -S localhost:8000 router.php
```

浏览器打开：<http://localhost:8000>

### 生产：宝塔面板

1. 网站根目录指向本仓库；**运行目录**选 **`/public`**。  
2. `open_basedir` 可设为 `…/public/:/tmp/`（不必放行仓库上级目录）。  
3. 配置伪静态（见下方 Nginx）。若暂时无法配置，在 `config.local.php` 中设 `'router_query' => true`，链接会变成 `index.php?r=/路径`。  

### Nginx 要点

伪静态：

```nginx
try_files $uri $uri/ /index.php?$query_string;
```

禁止直接访问敏感目录（与 `try_files` 同级或写在其前）：

```nginx
location ~ ^/(app|config|views|database)(/|$) {
    deny all;
    return 403;
}
```

### Apache

启用 `mod_rewrite`，使 `public/.htaccess` 生效。`app` / `config` / `views` / `database` 等子目录已有 `.htaccess` 拒绝直访。

### HTTPS 与样式

- HTTPS / 反代下 Session Cookie 会自动 `Secure`。  
- 需要强制跳转 HTTPS 时，在配置中设 `app.force_https => true`。  
- 主题样式：`public/assets/theme.css`（由布局模板引用）。  

### 子目录部署

若站点挂在 `/forum` 这类路径下，在 `config.local.php` 中设置：

```php
'app' => [
    'base_url' => '/forum',
    // ...
],
```

并同步调整伪静态与 Apache `RewriteBase`。

---

## 目录结构

可运行代码集中在 **`public/`**：

| 路径 | 说明 |
|------|------|
| `public/index.php` | Web 入口 |
| `public/router.php` | PHP 内置服务器路由 |
| `public/app/` | 引导、路由、认证、业务逻辑 |
| `public/views/` | 页面模板 |
| `public/config/` | `config.example.php`（示例）与 `config.local.php`（本地私密，勿提交） |
| `public/database/` | 表结构、迁移、`seed.php` |
| `public/assets/` | CSS / 前端静态资源 |
| `public/storage/` | 运行时数据（勿提交含隐私的内容） |
| `LICENSE` | 非商用许可证 |
| `.gitignore` | 忽略本地配置、依赖、日志等 |

仓库根目录可能还有历史部署残留文件；**以 `public/` 为准**部署即可。

### 站点 Logo

开源仓库**默认不附带**站点 Logo。部署后用管理员账号登录后台 → **站点 Logo** → 上传自己的图片即可。也可在 `config.local.php` 中设置 `app.logo_file` 指向 `public/` 下的静态文件路径。

---

## 可选功能配置（摘要）

均在 `public/config/config.local.php` 中配置，示例键名见 `config.example.php`：

| 配置段 | 用途 |
|--------|------|
| `sms` | Spug 等短信通道 |
| `cos` | 腾讯云 COS 图片 / 视频 |
| `geetest` | 行为验证码 |
| `moderation` | DeepSeek 内容审核 |
| `realname` | 实名二要素（阿里云市场 APPCODE） |
| `git_updates` | 首页「站点更新」（本地 git 或 GitHub API） |
| `firewall` / `anti_spam` / `access_challenge` | 防火墙、防刷、异常访问滑块 |

启用 COS 时，在 `public/` 下执行 `composer install`（见该目录 `composer.json`）。

---

## 安全提示

1. **密钥只放** `public/config/config.local.php`，不要提交到公开仓库。  
2. 从 `config.example.php` 复制后填写真实值；示例文件中的密钥字段应为**空占位符**。  
3. 若密钥曾误进 Git 历史，请立即在对应云平台（腾讯云 CAM、短信服务商等）**作废并轮换**。  
4. 默认管理员密码务必在首次登录后修改。  

---

## 贡献与反馈

欢迎 Issue / PR（学习与非商业场景）。提交前请确认未包含 `config.local.php`、真实密钥或服务器路径类文件。
