-- 表白：发送 / 收件箱 / 已读 / 忽略
-- mysql -u用户 -p 库名 < public/database/migration_confessions.sql

CREATE TABLE IF NOT EXISTS confessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_user_id INT UNSIGNED NOT NULL,
  to_user_id INT UNSIGNED NOT NULL,
  body VARCHAR(2000) NOT NULL,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('unread','read','ignored') NOT NULL DEFAULT 'unread',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_conf_to_status (to_user_id, status),
  KEY idx_conf_to_created (to_user_id, created_at),
  KEY idx_conf_from (from_user_id),
  CONSTRAINT fk_conf_from FOREIGN KEY (from_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_conf_to FOREIGN KEY (to_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
