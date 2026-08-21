-- 账号注销留痕：用户自助注销后禁止再登录，但系统保留注销记录供管理员审计
ALTER TABLE users
  ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL COMMENT '注销时间',
  ADD COLUMN deleted_reason VARCHAR(255) NULL DEFAULT NULL COMMENT '注销原因（用户填写）',
  ADD COLUMN deleted_ip VARCHAR(64) NULL DEFAULT NULL COMMENT '注销时 IP',
  ADD COLUMN deleted_user_agent VARCHAR(255) NULL DEFAULT NULL COMMENT '注销时 UA';

