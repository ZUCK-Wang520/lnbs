-- 赞助者：站长在后台可标记用户，公开主页展示赞助标签与头像框
ALTER TABLE users
  ADD COLUMN is_sponsor TINYINT(1) NOT NULL DEFAULT 0
  AFTER moderator_l2;
