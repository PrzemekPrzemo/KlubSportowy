-- Gymnastics grace-sports base migration
-- Wspolne tabele dla sportow ocenianych artystycznie: uklady i wystepy.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `gymnastics_routines` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`           INT UNSIGNED NOT NULL,
    `name`              VARCHAR(120) NOT NULL,
    `discipline`        VARCHAR(60) NOT NULL COMMENT 'np. floor/balance_beam/vault/uneven_bars dla gimnastyki',
    `difficulty_level`  VARCHAR(40) NULL,
    `duration_seconds`  INT UNSIGNED NULL,
    `description`       TEXT NULL,
    `is_active`         TINYINT(1) NOT NULL DEFAULT 1,
    KEY `idx_gymnastics_routines_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gymnastics_performances` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`           INT UNSIGNED NOT NULL,
    `member_id`         INT UNSIGNED NOT NULL,
    `routine_id`        INT UNSIGNED NULL,
    `discipline`        VARCHAR(60) NOT NULL,
    `technical_score`   DECIMAL(5,2) NULL COMMENT 'D-score',
    `artistic_score`    DECIMAL(5,2) NULL COMMENT 'E-score',
    `execution_score`   DECIMAL(5,2) NULL,
    `total_score`       DECIMAL(6,2) NULL,
    `deductions`        DECIMAL(5,2) NULL,
    `place`             INT UNSIGNED NULL,
    `performed_at`      DATETIME NOT NULL,
    `is_competition`    TINYINT(1) NOT NULL DEFAULT 0,
    `notes`             VARCHAR(255) NULL,
    KEY `idx_gymnastics_perf_member_disc` (`member_id`, `discipline`),
    KEY `idx_gymnastics_perf_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
