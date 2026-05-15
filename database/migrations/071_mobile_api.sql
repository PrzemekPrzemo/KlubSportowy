-- Migration 071: Mobile API v1 — per-member API tokens, announcement reads,
-- and extending member_notifications with a generic JSON `data` payload.

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `member_api_tokens` (
  `id`                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id`           INT UNSIGNED NOT NULL,
  `identity_id`         INT UNSIGNED NULL,
  `club_id`             INT UNSIGNED NOT NULL,
  `token_hash`          CHAR(64) NOT NULL,
  `refresh_token_hash`  CHAR(64) NULL,
  `device_token_id`     INT UNSIGNED NULL,
  `last_used_at`        DATETIME NULL,
  `expires_at`          DATETIME NOT NULL,
  `refresh_expires_at`  DATETIME NULL,
  `revoked_at`          DATETIME NULL,
  `user_agent`          VARCHAR(255) NULL,
  `ip_address`          VARCHAR(45) NULL,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_token_hash` (`token_hash`),
  UNIQUE KEY `uniq_refresh_token_hash` (`refresh_token_hash`),
  KEY `idx_member_club` (`member_id`, `club_id`),
  KEY `idx_expires` (`expires_at`, `revoked_at`),
  CONSTRAINT `fk_mat_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mat_club`   FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokeny REST API per-zawodnik (mobile app).';

CREATE TABLE IF NOT EXISTS `announcement_reads` (
  `announcement_id` INT UNSIGNED NOT NULL,
  `member_id`       INT UNSIGNED NOT NULL,
  `read_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`, `member_id`),
  KEY `idx_ar_member` (`member_id`),
  CONSTRAINT `fk_ar_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ar_member`       FOREIGN KEY (`member_id`)       REFERENCES `members`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Oznaczenia przeczytania ogloszen przez zawodnika.';

-- Extend existing member_notifications (from migration 035) with structured payload
-- so the mobile inbox can carry typed data (event_id, announcement_id, etc).
-- Guarded — MySQL has no IF NOT EXISTS for ADD COLUMN, so we swallow duplicate-column errors via a procedure.
DELIMITER //
DROP PROCEDURE IF EXISTS `__mn_add_data_col` //
CREATE PROCEDURE `__mn_add_data_col`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'member_notifications'
      AND COLUMN_NAME = 'data'
  ) THEN
    ALTER TABLE `member_notifications` ADD COLUMN `data` JSON NULL AFTER `body`;
  END IF;
END //
DELIMITER ;
CALL `__mn_add_data_col`();
DROP PROCEDURE `__mn_add_data_col`;

SET foreign_key_checks = 1;
