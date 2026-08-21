-- AI 拦截内容的人工复核队列 + 二级审核员标记
-- 若库名不是 lnbs，请修改 USE 或在 phpMyAdmin 中先选中数据库。
USE lnbs;

ALTER TABLE users
  ADD COLUMN moderator_l2 TINYINT(1) NOT NULL DEFAULT 0 COMMENT '二级审核员，可参与人工复核表决' AFTER banned;

CREATE TABLE IF NOT EXISTS moderation_appeals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_user_id INT UNSIGNED NOT NULL,
  action VARCHAR(32) NOT NULL,
  payload_json MEDIUMTEXT NOT NULL,
  ai_hint VARCHAR(500) NULL DEFAULT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL DEFAULT NULL,
  KEY idx_ma_status_created (status, created_at),
  KEY idx_ma_author (author_user_id),
  CONSTRAINT fk_ma_author FOREIGN KEY (author_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS moderation_appeal_votes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  appeal_id BIGINT UNSIGNED NOT NULL,
  voter_user_id INT UNSIGNED NOT NULL,
  decision ENUM('approve','reject') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mav_appeal_voter (appeal_id, voter_user_id),
  KEY idx_mav_appeal (appeal_id),
  CONSTRAINT fk_mav_appeal FOREIGN KEY (appeal_id) REFERENCES moderation_appeals (id) ON DELETE CASCADE,
  CONSTRAINT fk_mav_voter FOREIGN KEY (voter_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
