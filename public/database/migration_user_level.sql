-- 用户等级：经验值、每日签到、每日发帖奖励
USE lnbs;

ALTER TABLE users
  ADD COLUMN experience INT UNSIGNED NOT NULL DEFAULT 0 AFTER moderator_l2,
  ADD COLUMN level SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER experience,
  ADD COLUMN last_checkin_date DATE NULL DEFAULT NULL AFTER level,
  ADD COLUMN last_daily_post_xp_date DATE NULL DEFAULT NULL AFTER last_checkin_date;
t