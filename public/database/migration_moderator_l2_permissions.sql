-- 二级管理员细粒度权限（JSON），由站长在用户管理中勾选
ALTER TABLE users
  ADD COLUMN moderator_l2_perms TEXT NULL DEFAULT NULL COMMENT '二级管理员权限 JSON' AFTER moderator_l2;
