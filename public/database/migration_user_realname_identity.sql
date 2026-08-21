-- 实名身份信息加密存储（管理员后台可解密查看）
-- 需要在 config.local.php 配置 realname.storage_key（32+ 字符随机串），切勿提交仓库。
ALTER TABLE users
  ADD COLUMN realname_name_enc TEXT NULL DEFAULT NULL COMMENT '实名姓名（加密）',
  ADD COLUMN realname_idcard_enc TEXT NULL DEFAULT NULL COMMENT '实名身份证号（加密）';

