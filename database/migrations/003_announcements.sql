-- Migration: announcements (Phase 2.3)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `announcements` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `sport_id`    INT UNSIGNED NULL COMMENT 'NULL = ogólnoklubowe',
  `title`       VARCHAR(200) NOT NULL,
  `content`     TEXT NOT NULL,
  `priority`    ENUM('normal','important','urgent') NOT NULL DEFAULT 'normal',
  `target`      ENUM('staff','members','all','public') NOT NULL DEFAULT 'members',
  `published`   TINYINT(1) NOT NULL DEFAULT 1,
  `publish_from` DATETIME NULL,
  `publish_to`   DATETIME NULL,
  `author_id`   INT UNSIGNED NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_ann_club` (`club_id`),
  KEY `idx_ann_priority` (`priority`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`)  REFERENCES `sports`(`id`)  ON DELETE SET NULL,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ogłoszenia klubowe (widoczne dla zarządu/zawodników/publiczne)';

SET foreign_key_checks = 1;
