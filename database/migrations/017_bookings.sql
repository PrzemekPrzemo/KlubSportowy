-- Facility / venue booking system
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `facilities` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `name`        VARCHAR(150) NOT NULL,
  `type`        ENUM('boisko','sala','hala','tor','strzelnica','basen','kort','inne') NOT NULL DEFAULT 'inne',
  `capacity`    SMALLINT UNSIGNED NULL,
  `location`    VARCHAR(150) NULL,
  `description` TEXT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_fac_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Obiekty sportowe klubu';

CREATE TABLE IF NOT EXISTS `facility_bookings` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `facility_id`    INT UNSIGNED NOT NULL,
  `club_id`        INT UNSIGNED NOT NULL,
  `booked_by`      INT UNSIGNED NOT NULL,
  `booked_for_id`  INT UNSIGNED NULL COMMENT 'Opcjonalnie - rezerwacja dla zawodnika',
  `start_time`     DATETIME NOT NULL,
  `end_time`       DATETIME NOT NULL,
  `title`          VARCHAR(150) NOT NULL,
  `status`         ENUM('confirmed','pending','cancelled') NOT NULL DEFAULT 'confirmed',
  `notes`          TEXT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_fb_facility`  (`facility_id`),
  KEY `idx_fb_club`      (`club_id`),
  KEY `idx_fb_time`      (`start_time`, `end_time`),
  FOREIGN KEY (`facility_id`)   REFERENCES `facilities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)      ON DELETE CASCADE,
  FOREIGN KEY (`booked_by`)     REFERENCES `users`(`id`)      ON DELETE CASCADE,
  FOREIGN KEY (`booked_for_id`) REFERENCES `members`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rezerwacje obiektow sportowych';

SET foreign_key_checks = 1;
