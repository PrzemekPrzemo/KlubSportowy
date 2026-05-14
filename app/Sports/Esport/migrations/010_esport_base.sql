-- Esport base migration
-- Tabele dla gier i meczow esportowych (LoL, CS, Valorant, Dota, ...).
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `esport_games` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`    INT UNSIGNED NOT NULL,
    `code`       VARCHAR(40) NOT NULL,
    `name`       VARCHAR(80) NOT NULL,
    `publisher`  VARCHAR(80) NULL,
    UNIQUE KEY `uniq_club_code` (`club_id`, `code`),
    KEY `idx_esport_games_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `esport_matches` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`    INT UNSIGNED NOT NULL,
    `game_id`    INT UNSIGNED NOT NULL,
    `team_a`     VARCHAR(80) NOT NULL,
    `team_b`     VARCHAR(80) NOT NULL,
    `score_a`    INT UNSIGNED NULL,
    `score_b`    INT UNSIGNED NULL,
    `winner`     ENUM('A','B','draw') NULL,
    `played_at`  DATETIME NULL,
    `replay_url` VARCHAR(500) NULL,
    `stream_url` VARCHAR(500) NULL,
    KEY `idx_esport_matches_club` (`club_id`),
    KEY `idx_esport_matches_game` (`game_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`)        ON DELETE CASCADE,
    FOREIGN KEY (`game_id`) REFERENCES `esport_games`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
