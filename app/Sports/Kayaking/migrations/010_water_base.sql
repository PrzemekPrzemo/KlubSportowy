-- Kayaking water-sports base migration
-- Wspolne tabele dla sportow wodnych: baseny/akweny, dyscypliny, czasy.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `kayaking_pools` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`     INT UNSIGNED NOT NULL,
    `name`        VARCHAR(80) NOT NULL,
    `length_m`    DECIMAL(5,2) NOT NULL DEFAULT 25.00,
    `lanes`       TINYINT NULL,
    `is_outdoor`  TINYINT(1) NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    KEY `idx_kayaking_pools_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kayaking_disciplines` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`        VARCHAR(40) NOT NULL UNIQUE,
    `name`        VARCHAR(80) NOT NULL,
    `distance_m`  INT NULL,
    `stroke`      VARCHAR(40) NULL COMMENT 'freestyle/backstroke/breaststroke/butterfly/medley'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kayaking_times` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `member_id`      INT UNSIGNED NOT NULL,
    `discipline_id`  INT UNSIGNED NOT NULL,
    `pool_id`        INT UNSIGNED NULL,
    `time_ms`        INT UNSIGNED NOT NULL COMMENT 'czas w milisekundach',
    `recorded_at`    DATETIME NOT NULL,
    `is_official`    TINYINT(1) NOT NULL DEFAULT 0,
    `notes`          VARCHAR(255) NULL,
    KEY `idx_kayaking_times_member_disc` (`member_id`, `discipline_id`),
    KEY `idx_kayaking_times_club`         (`club_id`),
    FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)                       ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)                     ON DELETE CASCADE,
    FOREIGN KEY (`discipline_id`) REFERENCES `kayaking_disciplines`(`id`)       ON DELETE RESTRICT,
    FOREIGN KEY (`pool_id`)       REFERENCES `kayaking_pools`(`id`)             ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
