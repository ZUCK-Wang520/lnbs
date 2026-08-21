-- 登录 IP 记录 + 登录封禁（用户/ IP）
-- 执行前请备份数据库。

ALTER TABLE users
  ADD COLUMN last_login_ip VARCHAR(45) NULL DEFAULT NULL,
  ADD COLUMN last_login_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN login_banned_until DATETIME NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS ip_bans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  banned_until DATETIME NOT NULL,
  reason VARCHAR(255) NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_bans_ip (ip),
  INDEX idx_ip_bans_until (banned_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

