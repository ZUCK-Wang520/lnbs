-- 用户头像：相对 public/ 的路径，如 uploads/avatars/u1.jpg
-- 若库名不是 lnbs，请改成你的数据库名；或在 phpMyAdmin 左侧先点选数据库再执行本脚本（可删掉下面 USE 行）。
USE lnbs;

ALTER TABLE users
  ADD COLUMN avatar VARCHAR(255) NULL DEFAULT NULL AFTER nickname;
