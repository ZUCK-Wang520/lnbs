-- 主题点赞 + 浏览量
-- 执行前请备份数据库。

ALTER TABLE topics
  ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS topic_likes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_topic_likes_topic_user (topic_id, user_id),
  KEY idx_topic_likes_topic (topic_id),
  KEY idx_topic_likes_user (user_id),
  CONSTRAINT fk_topic_likes_topic FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE CASCADE,
  CONSTRAINT fk_topic_likes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

