-- Tennis racquet-sports base migration
-- Wspolne tabele dla sportow raketkowych: korty, mecze.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `tennis_courts` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`    INT UNSIGNED NOT NULL,
    `name`       VARCHAR(80) NOT NULL,
    `surface`    VARCHAR(40) NULL COMMENT 'clay/hard/grass/wood — zaleznie od sportu',
    `indoor`     TINYINT(1) NOT NULL DEFAULT 1,
    `notes`      VARCHAR(255) NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    KEY `idx_tennis_courts_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tennis_matches` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `tournament_id`  INT UNSIGNED NULL,
    `player_a_id`    INT UNSIGNED NOT NULL,
    `player_b_id`    INT UNSIGNED NOT NULL,
    `court_id`       INT UNSIGNED NULL,
    `played_at`      DATETIME NULL,
    `sets`           JSON NULL COMMENT 'np. [[6,4],[3,6],[7,5]] — sets/games',
    `winner_id`      INT UNSIGNED NULL,
    `notes`          VARCHAR(255) NULL,
    KEY `idx_tennis_m_club_date` (`club_id`, `played_at`),
    KEY `idx_tennis_m_pa`        (`player_a_id`),
    KEY `idx_tennis_m_pb`        (`player_b_id`),
    KEY `idx_tennis_m_court`     (`court_id`),
    FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)             ON DELETE CASCADE,
    FOREIGN KEY (`player_a_id`) REFERENCES `members`(`id`)           ON DELETE CASCADE,
    FOREIGN KEY (`player_b_id`) REFERENCES `members`(`id`)           ON DELETE CASCADE,
    FOREIGN KEY (`court_id`)    REFERENCES `tennis_courts`(`id`)  ON DELETE SET NULL,
    FOREIGN KEY (`winner_id`)   REFERENCES `members`(`id`)           ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
