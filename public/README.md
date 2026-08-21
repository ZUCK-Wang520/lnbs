# 鲁巴校园论坛

纯 PHP + MySQL 校园论坛。代码全部放在 **`public/`** 下，适配宝塔 **open_basedir 仅允许 `…/public/`**（防跨站）的配置。

## 环境要求

- PHP 8.1+（扩展：`pdo_mysql`、`mbstring`、`session`）
- MySQL 5.7+ / 8.x

## 安装

1. 创建数据库并导入表结构：

   - **本地新建库 `luba_forum`**：`mysql -u root -p < public/database/schema.sql`
   - **面板已建好库（例如库名 `lnbs`）**：`mysql -u lnbs -p lnbs < public/database/schema_lnbs.sql`（或在 phpMyAdmin 中导入 `public/database/schema_lnbs.sql`）

2. 配置数据库：复制 `public/config/config.example.php` 为 `public/config/config.local.php`，修改 `dsn`、`user`、`pass`。

3. 写入初始数据（在项目仓库根目录执行）：

   ```bash
   php public/database/seed.php
   ```

   默认管理员：`admin@luba.local` / `admin123`（登录后请修改密码）。种子会同时创建**匿名发帖占位账号**。

4. **匿名发帖（已有旧库时）**：若表 `topics` / `posts` 尚无 `is_anonymous`、`anon_nickname` 字段，请执行一次：

   ```bash
   mysql -u 用户 -p 数据库名 < public/database/migration_anonymous.sql
   ```

   然后再执行 `php public/database/seed.php`（会补全占位账号）。

## 运行

**宝塔**

1. 网站 **运行目录** 选 **`/public`**（网站目录一般为 `/www/wwwroot/lnbs.fun`，内含本仓库的 `public` 子目录）。
2. **open_basedir** 可保持 **`…/public/:/tmp/`**，无需再放行上级目录；PHP 只读写 `public` 内文件即可。
3. **`app` / `config` / `views` / `database`** 已用 **Apache `.htaccess`** 禁止浏览器直接访问；若用 **Nginx**，请在站点配置中增加（与 `try_files` 同级或在其前）：

```nginx
location ~ ^/(app|config|views|database)(/|$) {
    deny all;
    return 403;
}
```

4. **伪静态**：Nginx 需 `try_files $uri $uri/ /index.php?$query_string;`；若暂时不能配，在 `public/config/config.local.php` 设 **`'router_query' => true`**（链接为 `index.php?r=/路径`）。

**Apache**：启用 `mod_rewrite`，`public/.htaccess` 生效；敏感子目录由各自的 `.htaccess` 拒绝访问。

**HTTPS**：Session 在 HTTPS / 反代下会自动开启 `cookie_secure`。

**样式**：编辑 **`public/assets/theme.css`**（由 `public/views/layout.php` 内联输出）。

**PHP 内置服务器**（开发）：

```bash
cd public
php -S localhost:8000 router.php
```

## 目录说明（均在 `public/` 下）

| 路径 | 说明 |
|------|------|
| `index.php` | 入口 |
| `app/` | 引导、路由、数据库、认证 |
| `views/` | 模板 |
| `config/` | `config.local.php`（勿提交仓库） |
| `database/` | `schema*.sql`、`seed.php` |
| `assets/` | `theme.css` 等 |

## 子目录部署

在 `public/config/config.local.php` 中设置 `app.base_url`（例如 `/forum`），并相应调整伪静态与 `RewriteBase`。
