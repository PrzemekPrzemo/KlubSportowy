-- Migration: 2FA TOTP (Phase 6.1)
SET foreign_key_checks = 0;

ALTER TABLE `users`
  ADD COLUMN `totp_secret`     VARCHAR(64) NULL AFTER `is_super_admin`,
  ADD COLUMN `totp_enabled`    TINYINT(1)  NOT NULL DEFAULT 0 AFTER `totp_secret`,
  ADD COLUMN `totp_confirmed_at` DATETIME NULL AFTER `totp_enabled`;

CREATE TABLE IF NOT EXISTS `totp_backup_codes` (
  `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`   INT UNSIGNED NOT NULL,
  `code_hash` VARCHAR(255) NOT NULL,
  `used_at`   DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tbc_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kody zapasowe 2FA (jednorazowe)';

SET foreign_key_checks = 1;
