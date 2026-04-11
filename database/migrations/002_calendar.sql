-- Migration: calendar (Phase 2.1b)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `calendar_event_categories` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `name`       VARCHAR(80)  NOT NULL,
  `color`      VARCHAR(20)  NOT NULL DEFAULT '#0d6efd',
  `icon`       VARCHAR(50)  NULL,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_cec_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kategorie wydarzeń w kalendarzu (np. mecz ligowy, trening, obóz)';

CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NULL,
  `sport_id`    INT UNSIGNED NULL,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `location`    VARCHAR(200) NULL,
  `start_at`    DATETIME NOT NULL,
  `end_at`      DATETIME NULL,
  `all_day`     TINYINT(1)  NOT NULL DEFAULT 0,
  `visibility`  ENUM('private','club','public') NOT NULL DEFAULT 'club',
  `link_type`   ENUM('none','training','event','match') NOT NULL DEFAULT 'none',
  `link_id`     INT UNSIGNED NULL COMMENT 'ID w odpowiedniej tabeli (training/event)',
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_calev_club`  (`club_id`),
  KEY `idx_calev_start` (`start_at`),
  FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)                   ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `calendar_event_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sport_id`)    REFERENCES `sports`(`id`)                  ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`)                   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wpisy kalendarza klubu (generyczne — łączone z treningami/wydarzeniami przez link_type + link_id)';

SET foreign_key_checks = 1;
