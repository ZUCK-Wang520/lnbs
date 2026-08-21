-- 个人「喜欢」，公开主页可见。若列已存在会报错可忽略。
-- 若库名不是 lnbs，请改成你的数据库名；或在 phpMyAdmin 中先选中数据库再执行（可删掉下面 USE 行）。
USE lnbs;

ALTER TABLE users
  ADD COLUMN profile_likes TEXT NULL DEFAULT NULL COMMENT '个人喜欢，可多行' AFTER avatar;
