-- Migration 032: Error log table for admin error viewer
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `error_log` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `level` ENUM('debug','info','warning','error','critical') NOT NULL DEFAULT 'error',
  `message` TEXT NOT NULL,
  `context` JSON NULL,
  `file` VARCHAR(500) NULL,
  `line` INT UNSIGNED NULL,
  `trace` LONGTEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_id` INT UNSIGNED NULL,
  `club_id` INT UNSIGNED NULL,
  `url` VARCHAR(1000) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_error_level` (`level`),
  INDEX `idx_error_created` (`created_at`),
  INDEX `idx_error_club` (`club_id`),
  INDEX `idx_error_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
