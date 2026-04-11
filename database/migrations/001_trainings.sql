-- Migration: trainings (Phase 2.1)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `trainings` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `sport_id`        INT UNSIGNED NULL,
  `club_sport_id`   INT UNSIGNED NULL,
  `name`            VARCHAR(150) NOT NULL,
  `description`     TEXT NULL,
  `location`        VARCHAR(150) NULL,
  `start_time`      DATETIME NOT NULL,
  `end_time`        DATETIME NULL,
  `max_participants` SMALLINT UNSIGNED NULL,
  `instructor_id`   INT UNSIGNED NULL COMMENT 'user-instruktor',
  `status`          ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') NOT NULL DEFAULT 'zaplanowany',
  `created_by`      INT UNSIGNED NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_trainings_club` (`club_id`),
  KEY `idx_trainings_time` (`start_time`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`)      REFERENCES `sports`(`id`)      ON DELETE SET NULL,
  FOREIGN KEY (`club_sport_id`) REFERENCES `club_sports`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`)       ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zajęcia treningowe klubu';

CREATE TABLE IF NOT EXISTS `training_attendees` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `training_id`  INT UNSIGNED NOT NULL,
  `member_id`    INT UNSIGNED NOT NULL,
  `status`       ENUM('zapisany','obecny','nieobecny','spozniony','wypisany') NOT NULL DEFAULT 'zapisany',
  `registered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes`        VARCHAR(255) NULL,
  UNIQUE KEY `uq_training_member` (`training_id`, `member_id`),
  KEY `idx_ta_member` (`member_id`),
  FOREIGN KEY (`training_id`) REFERENCES `trainings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Obecność zawodników na treningach';

SET foreign_key_checks = 1;
