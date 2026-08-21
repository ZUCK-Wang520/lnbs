-- 注册短信验证码：users.phone + 发送日志与防刷
-- 在已运行的库上执行：mysql -u用户 -p 库名 < public/database/migration_register_sms.sql

ALTER TABLE users
  ADD COLUMN phone VARCHAR(20) NULL DEFAULT NULL AFTER email;

CREATE UNIQUE INDEX uq_users_phone ON users (phone);

CREATE TABLE IF NOT EXISTS sms_send_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  phone VARCHAR(20) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sms_phone_time (phone, created_at),
  KEY idx_sms_ip_time (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
