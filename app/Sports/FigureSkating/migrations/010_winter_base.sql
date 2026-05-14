-- FigureSkating winter-sports base migration
-- Wspolne tabele dla sportow zimowych: trasy/lodowiska, przebiegi i czasy.
-- FigureSkating uzywa `_rinks` zamiast `_slopes`.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `figure_skating_rinks` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`       INT UNSIGNED NOT NULL,
    `name`          VARCHAR(80) NOT NULL,
    `location`      VARCHAR(120) NULL,
    `difficulty`    ENUM('easy','medium','hard','expert') NULL,
    `length_m`      INT NULL,
    `elevation_m`   INT NULL,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    KEY `idx_figure_skating_rinks_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `figure_skating_runs` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NOT NULL,
    `member_id`       INT UNSIGNED NOT NULL,
    `rink_id`         INT UNSIGNED NULL,
    `discipline`      VARCHAR(40) NOT NULL COMMENT 'short_program/free_skate/ice_dance/pairs',
    `time_ms`         INT UNSIGNED NULL,
    `score`           DECIMAL(6,2) NULL COMMENT 'dla freestyle/judging',
    `place`           INT UNSIGNED NULL,
    `is_competition`  TINYINT(1) NOT NULL DEFAULT 0,
    `recorded_at`     DATETIME NOT NULL,
    `notes`           VARCHAR(255) NULL,
    KEY `idx_figure_skating_runs_member` (`member_id`),
    KEY `idx_figure_skating_runs_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
