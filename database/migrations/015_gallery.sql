-- Gallery: albums & photos
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `gallery_albums` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `sport_id`    INT UNSIGNED NULL,
  `event_id`    INT UNSIGNED NULL,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `cover_path`  VARCHAR(255) NULL,
  `is_public`   TINYINT(1) NOT NULL DEFAULT 0,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ga_club` (`club_id`),
  KEY `idx_ga_sport` (`sport_id`),
  KEY `idx_ga_event` (`event_id`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`)   REFERENCES `sports`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`event_id`)   REFERENCES `events`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Albumy zdjec galerii klubowej';

CREATE TABLE IF NOT EXISTS `gallery_photos` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `album_id`       INT UNSIGNED NOT NULL,
  `file_path`      VARCHAR(255) NOT NULL,
  `thumbnail_path` VARCHAR(255) NULL,
  `caption`        VARCHAR(255) NULL,
  `uploaded_by`    INT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_gp_album` (`album_id`),
  FOREIGN KEY (`album_id`)    REFERENCES `gallery_albums`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zdjecia w albumach galerii';

SET foreign_key_checks = 1;
