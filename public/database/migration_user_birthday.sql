-- 用户生日：个人中心可设置；生日当天首页向全站展示祝福
ALTER TABLE users
  ADD COLUMN birthday DATE NULL DEFAULT NULL COMMENT '公历生日（月日用于每年匹配）';
