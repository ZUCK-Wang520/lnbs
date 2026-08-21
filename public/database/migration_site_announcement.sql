-- 全站公告（站长在后台「管理首页」编辑）
-- 执行前请备份数据库。

CREATE TABLE IF NOT EXISTS site_announcement (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  body TEXT NULL,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO site_announcement (id, enabled, body) VALUES (1, 0, NULL);
