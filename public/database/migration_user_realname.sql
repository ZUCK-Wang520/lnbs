-- 实名认证：管理员可指定用户具备「实名认证」资格；用户二要素核验通过后标记已实名。
-- 注意：身份信息（姓名/身份证号）将以加密形式存储，解密密钥仅放在 config.local.php（勿提交仓库）。
ALTER TABLE users
  ADD COLUMN realname_allowed TINYINT(1) NOT NULL DEFAULT 0 COMMENT '管理员允许发起实名认证',
  ADD COLUMN realname_verified TINYINT(1) NOT NULL DEFAULT 0 COMMENT '已通过实名认证',
  ADD COLUMN realname_verified_at DATETIME NULL DEFAULT NULL;
