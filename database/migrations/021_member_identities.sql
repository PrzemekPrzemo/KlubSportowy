-- Migration 021: Unified member identities (cross-club identity)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `member_identities` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `identity_hash`     VARCHAR(64)  NOT NULL UNIQUE COMMENT 'SHA-256 of primary identifier',
  `portal_email`      VARCHAR(120) NOT NULL UNIQUE,
  `portal_password`   VARCHAR(255) NULL,
  `portal_last_login` DATETIME     NULL,
  `display_name`      VARCHAR(120) NOT NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `members` ADD COLUMN `identity_id` INT UNSIGNED NULL AFTER `club_id`;
ALTER TABLE `members` ADD CONSTRAINT `fk_member_identity` FOREIGN KEY (`identity_id`) REFERENCES `member_identities`(`id`) ON DELETE SET NULL;

SET foreign_key_checks = 1;
