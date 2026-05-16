-- ============================================================
-- Migration: 084_trainer_scheduling.sql
-- Trener: dostepnosc cykliczna, urlopy, audit konfliktow
-- Cross-club aware: trener moze byc w wielu klubach,
-- availability moze byc globalna (club_id NULL) lub per-klub.
-- ============================================================
SET foreign_key_checks = 0;

-- Dostepnosc cykliczna (np. Mon-Fri 9-17, Sat 10-14)
CREATE TABLE IF NOT EXISTS `trainer_availability` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED NOT NULL,
  `club_id`     INT UNSIGNED NULL COMMENT 'NULL = globalna dostepnosc trenera, INT = ograniczona per-klub',
  `weekday`     TINYINT NOT NULL COMMENT '1=mon..7=sun',
  `time_start`  TIME NOT NULL,
  `time_end`    TIME NOT NULL,
  `valid_from`  DATE NULL COMMENT 'od kiedy obowiazuje (NULL = od zawsze)',
  `valid_until` DATE NULL COMMENT 'do kiedy (NULL = bezterminowo)',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ta_user_weekday` (`user_id`, `weekday`),
  KEY `idx_ta_club` (`club_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cykliczna dostepnosc trenera per weekday';

-- Urlopy / nieobecnosci (override availability)
CREATE TABLE IF NOT EXISTS `trainer_leaves` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED NOT NULL,
  `leave_type`  ENUM('vacation','sick','training','other') NOT NULL DEFAULT 'vacation',
  `date_from`   DATE NOT NULL,
  `date_to`     DATE NOT NULL,
  `reason`      VARCHAR(500) NULL,
  `approved_by` INT UNSIGNED NULL,
  `approved_at` DATETIME NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tl_user_date` (`user_id`, `date_from`, `date_to`),
  FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Urlopy/nieobecnosci trenera (cross-club)';

-- Audit konfliktow planowania
CREATE TABLE IF NOT EXISTS `trainer_schedule_conflicts` (
  `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT UNSIGNED NOT NULL,
  `training_id`    INT UNSIGNED NULL,
  `club_id`        INT UNSIGNED NOT NULL,
  `conflict_type`  ENUM('overlap','outside_availability','during_leave','double_booking') NOT NULL,
  `starts_at`      DATETIME NOT NULL,
  `ends_at`        DATETIME NOT NULL,
  `details`        TEXT NULL,
  `resolved`       TINYINT(1) NOT NULL DEFAULT 0,
  `detected_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tsc_user_unresolved` (`user_id`, `resolved`),
  KEY `idx_tsc_club` (`club_id`),
  KEY `idx_tsc_training` (`training_id`),
  FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`training_id`) REFERENCES `trainings`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historia wykrytych konfliktow planowania trenerow';

SET foreign_key_checks = 1;
