-- Migration 010: Demo tokens
-- Allows super admin to generate shareable demo links

CREATE TABLE IF NOT EXISTS `demo_tokens` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `token`      VARCHAR(64)  NOT NULL UNIQUE,
  `club_id`    INT UNSIGNED NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokeny demo do automatycznego logowania do klubu demo';
