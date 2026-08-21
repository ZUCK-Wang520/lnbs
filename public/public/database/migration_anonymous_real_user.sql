-- 匿名发帖记录真实账号（供管理员查看）；需登录后匿名时写入 real_user_id
-- mysql -u用户 -p 库名 < public/database/migration_anonymous_real_user.sql

ALTER TABLE topics
  ADD COLUMN real_user_id INT UNSIGNED NULL DEFAULT NULL AFTER user_id,
  ADD KEY idx_topics_real_user (real_user_id),
  ADD CONSTRAINT fk_topics_real_user FOREIGN KEY (real_user_id) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE posts
  ADD COLUMN real_user_id INT UNSIGNED NULL DEFAULT NULL AFTER user_id,
  ADD KEY idx_posts_real_user (real_user_id),
  ADD CONSTRAINT fk_posts_real_user FOREIGN KEY (real_user_id) REFERENCES users (id) ON DELETE SET NULL;
