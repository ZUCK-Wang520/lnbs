-- 已有库升级：匿名发帖（执行一次即可）
-- mysql -u lnbs -p lnbs < public/database/migration_anonymous.sql

ALTER TABLE topics
  ADD COLUMN is_anonymous TINYINT(1) NOT NULL DEFAULT 0 AFTER user_id,
  ADD COLUMN anon_nickname VARCHAR(32) NULL DEFAULT NULL AFTER is_anonymous;

ALTER TABLE posts
  ADD COLUMN is_anonymous TINYINT(1) NOT NULL DEFAULT 0 AFTER user_id,
  ADD COLUMN anon_nickname VARCHAR(32) NULL DEFAULT NULL AFTER is_anonymous;
