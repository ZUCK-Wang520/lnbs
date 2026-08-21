-- 已在面板中创建数据库「lnbs」时使用本文件导入表结构（勿再执行 luba_forum 的建库语句）。
-- 命令示例：mysql -u lnbs -p lnbs < database/schema_lnbs.sql

USE lnbs;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(20) NULL DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  nickname VARCHAR(64) NOT NULL,
  avatar VARCHAR(255) NULL DEFAULT NULL,
  profile_likes TEXT NULL DEFAULT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  banned TINYINT(1) NOT NULL DEFAULT 0,
  moderator_l2 TINYINT(1) NOT NULL DEFAULT 0,
  moderator_l2_perms TEXT NULL DEFAULT NULL COMMENT '二级管理员权限 JSON',
  is_sponsor TINYINT(1) NOT NULL DEFAULT 0,
  experience INT UNSIGNED NOT NULL DEFAULT 0,
  level SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  last_checkin_date DATE NULL DEFAULT NULL,
  last_daily_post_xp_date DATE NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_send_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  phone VARCHAR(20) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sms_phone_time (phone, created_at),
  KEY idx_sms_ip_time (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  description VARCHAR(500) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_boards_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS topics (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  board_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  real_user_id INT UNSIGNED NULL DEFAULT NULL,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  anon_nickname VARCHAR(32) NULL DEFAULT NULL,
  title VARCHAR(200) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  pinned TINYINT(1) NOT NULL DEFAULT 0,
  locked TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_topics_board (board_id),
  KEY idx_topics_user (user_id),
  KEY idx_topics_real_user (real_user_id),
  CONSTRAINT fk_topics_board FOREIGN KEY (board_id) REFERENCES boards (id) ON DELETE CASCADE,
  CONSTRAINT fk_topics_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_topics_real_user FOREIGN KEY (real_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic_id INT UNSIGNED NOT NULL,
  parent_post_id INT UNSIGNED NULL DEFAULT NULL,
  user_id INT UNSIGNED NOT NULL,
  real_user_id INT UNSIGNED NULL DEFAULT NULL,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  anon_nickname VARCHAR(32) NULL DEFAULT NULL,
  body MEDIUMTEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_posts_topic (topic_id),
  KEY idx_posts_parent (parent_post_id),
  KEY idx_posts_user (user_id),
  KEY idx_posts_real_user (real_user_id),
  CONSTRAINT fk_posts_topic FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE CASCADE,
  CONSTRAINT fk_posts_parent FOREIGN KEY (parent_post_id) REFERENCES posts (id) ON DELETE CASCADE,
  CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
  CONSTRAINT fk_posts_real_user FOREIGN KEY (real_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS topic_reply_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient_user_id INT UNSIGNED NOT NULL,
  topic_id INT UNSIGNED NOT NULL,
  post_id INT UNSIGNED NOT NULL,
  read_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_trn_post_recipient (post_id, recipient_user_id),
  KEY idx_trn_recipient_unread (recipient_user_id, read_at),
  KEY idx_trn_topic (topic_id),
  CONSTRAINT fk_trn_recipient FOREIGN KEY (recipient_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_trn_topic FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE CASCADE,
  CONSTRAINT fk_trn_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS confessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_user_id INT UNSIGNED NOT NULL,
  to_user_id INT UNSIGNED NOT NULL,
  body VARCHAR(2000) NOT NULL,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('unread','read','ignored') NOT NULL DEFAULT 'unread',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_conf_to_status (to_user_id, status),
  KEY idx_conf_to_created (to_user_id, created_at),
  KEY idx_conf_from (from_user_id),
  CONSTRAINT fk_conf_from FOREIGN KEY (from_user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_conf_to FOREIGN KEY (to_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_announcement (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  body TEXT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO site_announcement (id, enabled, body) VALUES (1, 0, NULL);

CREATE TABLE IF NOT EXISTS admin_audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT UNSIGNED NOT NULL,
  actor_nickname VARCHAR(64) NULL,
  action VARCHAR(80) NOT NULL,
  summary VARCHAR(500) NULL,
  meta_json MEDIUMTEXT NULL,
  request_path VARCHAR(512) NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_aal_actor_created (actor_user_id, created_at),
  KEY idx_aal_action_created (action(32), created_at),
  KEY idx_aal_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
