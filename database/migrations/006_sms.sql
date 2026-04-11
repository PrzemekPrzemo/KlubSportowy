-- Migration: SMS queue (Phase 4.2)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `sms_queue` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `to_phone`   VARCHAR(20) NOT NULL,
  `to_name`    VARCHAR(120) NULL,
  `message`    VARCHAR(500) NOT NULL,
  `status`     ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `error`      TEXT NULL,
  `sent_at`    DATETIME NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_smsq_club`   (`club_id`),
  KEY `idx_smsq_status` (`status`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kolejka wiadomości SMS';

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NULL COMMENT 'adresat (admin/trener) — NULL = dla całego klubu',
  `type`       VARCHAR(60) NOT NULL,
  `title`      VARCHAR(200) NOT NULL,
  `body`       TEXT NULL,
  `link`       VARCHAR(255) NULL,
  `read_at`    DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_notif_user` (`user_id`, `read_at`),
  KEY `idx_notif_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Powiadomienia in-app (dzwoneczek w navbarze)';

SET foreign_key_checks = 1;
