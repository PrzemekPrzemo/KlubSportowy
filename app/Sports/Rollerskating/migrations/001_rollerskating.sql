-- Rollerskating plugin migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `rollerskating_equipment` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `member_id`   INT UNSIGNED NULL COMMENT 'NULL = sprzet klubowy',
  `type`        ENUM('wrotki','ochraniacze','kask','buty','kombinezon','inne') NOT NULL DEFAULT 'wrotki',
  `brand`       VARCHAR(80) NULL,
  `model`       VARCHAR(80) NULL,
  `size`        VARCHAR(20) NULL,
  `condition_state` ENUM('nowy','dobry','uzytkowy','do_serwisu','wycofany') NOT NULL DEFAULT 'dobry',
  `purchase_date` DATE NULL,
  `notes`       TEXT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_re_club` (`club_id`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rollerskating_times` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`       INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `discipline_id` INT UNSIGNED NULL,
  `event_id`      INT UNSIGNED NULL COMMENT 'powiazanie z events',
  `distance`      VARCHAR(30) NULL COMMENT 'np. 500m, 1000m, maraton',
  `time_ms`       INT UNSIGNED NOT NULL COMMENT 'wynik w milisekundach',
  `record_date`   DATE NOT NULL,
  `is_personal_best` TINYINT(1) NOT NULL DEFAULT 0,
  `notes`         TEXT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_rt_club` (`club_id`),
  KEY `idx_rt_member` (`member_id`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`event_id`)      REFERENCES `events`(`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
