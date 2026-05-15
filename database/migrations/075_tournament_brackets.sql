-- Migration 075: Tournament brackets (single-elim, double-elim, round-robin, swiss)
-- Adds bracket configuration + per-participant seeding + extra columns on tournament_matches
-- needed for proper advancement (parent_match_id, bracket_position, bracket_side).

SET foreign_key_checks = 0;

-- Konfiguracja drabinki dla turnieju (1:1 z `tournaments`)
CREATE TABLE IF NOT EXISTS `tournament_brackets` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`           INT UNSIGNED NOT NULL,
    `tournament_id`     INT UNSIGNED NOT NULL,
    `bracket_type`      ENUM('single_elimination','double_elimination','round_robin','swiss') NOT NULL DEFAULT 'single_elimination',
    `seed_method`       ENUM('random','manual','ranking','snake') NOT NULL DEFAULT 'random',
    `third_place_match` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Tylko SE — czy generowac mecz o 3 miejsce',
    `rounds_count`      TINYINT UNSIGNED NULL COMMENT 'Auto-calc dla SE/DE; dla round_robin = N-1',
    `is_locked`         TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Po starcie nie mozna edytowac seeding',
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_tournament` (`tournament_id`),
    KEY `idx_club` (`club_id`),
    FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)       ON DELETE CASCADE,
    FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-uczestnik seed (numer w drabince)
CREATE TABLE IF NOT EXISTS `tournament_seeds` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `tournament_id`  INT UNSIGNED NOT NULL,
    `participant_id` INT UNSIGNED NOT NULL,
    `seed_number`    SMALLINT UNSIGNED NOT NULL COMMENT '1-based, 1 = top seed',
    `bracket_side`   ENUM('upper','lower') NOT NULL DEFAULT 'upper',
    UNIQUE KEY `uniq_tournament_participant` (`tournament_id`,`participant_id`),
    UNIQUE KEY `uniq_tournament_seed`        (`tournament_id`,`seed_number`),
    KEY `idx_club` (`club_id`),
    FOREIGN KEY (`club_id`)        REFERENCES `clubs`(`id`)                   ON DELETE CASCADE,
    FOREIGN KEY (`tournament_id`)  REFERENCES `tournaments`(`id`)             ON DELETE CASCADE,
    FOREIGN KEY (`participant_id`) REFERENCES `tournament_participants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rozszerzenie tournament_matches o pola advancement.
-- Idempotentnie: probujemy ADD COLUMN, jesli juz istnieje to zignoruj (MySQL nie ma ADD COLUMN IF NOT EXISTS w 5.7,
-- ale od 8.0.29 jest; uzywamy `IF NOT EXISTS` co dziala w MariaDB i nowszych MySQL).
ALTER TABLE `tournament_matches`
    ADD COLUMN IF NOT EXISTS `bracket_position` SMALLINT UNSIGNED NULL COMMENT '0-based pozycja w rundzie',
    ADD COLUMN IF NOT EXISTS `parent_match_id`  INT UNSIGNED NULL COMMENT 'Mecz w kolejnej rundzie do ktorego trafia zwyciezca (SE/DE-WB)',
    ADD COLUMN IF NOT EXISTS `loser_match_id`   INT UNSIGNED NULL COMMENT 'Tylko DE: mecz w LB dla przegranego (z WB)',
    ADD COLUMN IF NOT EXISTS `bracket_side`     ENUM('upper','lower','final','third_place') NOT NULL DEFAULT 'upper',
    ADD COLUMN IF NOT EXISTS `slot_in_parent`   TINYINT UNSIGNED NULL COMMENT '0 lub 1 — ktory slot (player1 vs player2) w parent_match',
    ADD COLUMN IF NOT EXISTS `is_bye`           TINYINT(1) NOT NULL DEFAULT 0;

-- Klucze pomocnicze (idempotentnie pominiemy bledy dla "duplicate key").
-- MySQL nie ma `CREATE INDEX IF NOT EXISTS`, wiec uzywamy procedury inline w razie potrzeby.
-- Dla prostoty zakladamy ze migracja jest uruchamiana raz; gdy migrator wykrywa duplikat — przejdzie do nastepnej.
ALTER TABLE `tournament_matches`
    ADD KEY `idx_parent_match` (`parent_match_id`),
    ADD KEY `idx_tour_round_pos` (`tournament_id`, `round`, `bracket_position`);

SET foreign_key_checks = 1;
