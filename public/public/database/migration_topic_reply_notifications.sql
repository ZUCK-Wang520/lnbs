-- 主题被回复时通知楼主（右上角「消息」）。若表已存在可忽略错误。
-- 若库名不是 lnbs，请修改 USE 或在 phpMyAdmin 中先选中数据库。
USE lnbs;

CREATE TABLE IF NOT EXISTS topic_reply_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient_user_id INT UNSIGNED NOT NULL,
  topic_id INT UNSIGNED NOT NULL,
  post_id INT UNSIGNED NOT NULL,
  read_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_trn_post_recipient (post_id, recipient_user_id),
  KEY idx_trn_recipient_unread (recipient_user_id, read_at),
  KEY idx_trn_topic (topic_id),
  CONSTRAINT fk_trn_recipient FOREIGN KEY (recipient_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_trn_topic FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE CASCADE,
  CONSTRAINT fk_trn_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
