-- GDPR migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `member_consents` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`      INT UNSIGNED NOT NULL,
  `member_id`    INT UNSIGNED NOT NULL,
  `consent_type` ENUM('rodo','marketing','wizerunek','newsletter','profilowanie') NOT NULL,
  `granted`      TINYINT(1) NOT NULL DEFAULT 0,
  `granted_at`   DATETIME NULL,
  `revoked_at`   DATETIME NULL,
  `ip_address`   VARCHAR(45) NULL,
  `notes`        VARCHAR(255) NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_consent` (`club_id`, `member_id`, `consent_type`),
  KEY `idx_mc_member` (`member_id`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zgody RODO zawodnikow';

ALTER TABLE `members`
  ADD COLUMN `anonymized_at` DATETIME NULL AFTER `portal_last_login`;

SET foreign_key_checks = 1;
