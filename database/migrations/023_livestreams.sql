SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `livestreams` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`      INT UNSIGNED NOT NULL,
  `event_id`     INT UNSIGNED NULL,
  `title`        VARCHAR(200) NOT NULL,
  `platform`     ENUM('youtube','twitch','facebook','inne') NOT NULL DEFAULT 'youtube',
  `stream_url`   VARCHAR(500) NOT NULL,
  `embed_code`   TEXT NULL,
  `status`       ENUM('zaplanowana','na_zywo','zakonczona') NOT NULL DEFAULT 'zaplanowana',
  `scheduled_at` DATETIME NULL,
  `started_at`   DATETIME NULL,
  `ended_at`     DATETIME NULL,
  `viewers_peak` INT UNSIGNED NULL,
  `is_public`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`   INT UNSIGNED NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_ls_club` (`club_id`),
  KEY `idx_ls_status` (`status`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`event_id`)   REFERENCES `events`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
