-- 后台操作审计日志（站长在 /admin/audit-log 查看）
CREATE TABLE IF NOT EXISTS admin_audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT UNSIGNED NOT NULL COMMENT '操作者用户 ID',
  actor_nickname VARCHAR(64) NULL COMMENT '写入时的昵称快照',
  action VARCHAR(80) NOT NULL COMMENT '操作类型，如 user.ban',
  summary VARCHAR(500) NULL COMMENT '简短说明',
  meta_json MEDIUMTEXT NULL COMMENT 'JSON 详情',
  request_path VARCHAR(512) NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_aal_actor_created (actor_user_id, created_at),
  KEY idx_aal_action_created (action(32), created_at),
  KEY idx_aal_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
