-- Migration 033: Security events log for admin security viewer
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `security_events` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_type` ENUM(
    'login_failed','login_success','login_2fa_failed','logout',
    'csrf_violation','rate_limit_hit','password_change',
    '2fa_enabled','2fa_disabled',
    'impersonation_start','impersonation_stop','account_locked'
  ) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_id` INT UNSIGNED NULL,
  `user_agent` VARCHAR(500) NULL,
  `url` VARCHAR(1000) NULL,
  `details` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sec_type` (`event_type`),
  INDEX `idx_sec_ip` (`ip_address`),
  INDEX `idx_sec_created` (`created_at`),
  INDEX `idx_sec_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
