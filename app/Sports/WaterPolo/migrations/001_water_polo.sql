-- Water polo (full base).
-- Wzorzec: app/Sports/Futsal/migrations/001_futsal.sql
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS water_polo_teams (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(150) NOT NULL,
    category   ENUM('senior_m','senior_k','junior_m','junior_k','U18','U16','U14','dzieci') DEFAULT 'senior_m',
    coach_id   INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wpt_club (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS water_polo_players (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    team_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    cap_number     SMALLINT UNSIGNED NULL COMMENT 'numer czepka',
    position       ENUM('bramkarz','obronca','skrzydlowy','center_forward','driver','uniwersalny') DEFAULT 'uniwersalny',
    is_captain     TINYINT(1) NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_wpp (team_id, member_id),
    KEY idx_wpp_club (club_id),
    FOREIGN KEY (club_id)   REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id)   REFERENCES water_polo_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS water_polo_matches (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    home_team_id   INT UNSIGNED NOT NULL,
    away_team_name VARCHAR(150) NULL,
    match_date     DATETIME NOT NULL,
    location       VARCHAR(200) NULL,
    home_score     SMALLINT UNSIGNED DEFAULT 0,
    away_score     SMALLINT UNSIGNED DEFAULT 0,
    status         ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') DEFAULT 'zaplanowany',
    notes          TEXT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wpm_club (club_id),
    KEY idx_wpm_date (match_date),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (home_team_id) REFERENCES water_polo_teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS water_polo_events (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    match_id     INT UNSIGNED NOT NULL,
    player_id    INT UNSIGNED NOT NULL,
    event_type   ENUM('gol','asysta','wykluczenie','wykluczenie_5','obrona_brm','rzut_karny','przewinienie') NOT NULL,
    quarter      TINYINT UNSIGNED NULL,
    second_mark  SMALLINT UNSIGNED NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wpe_match (match_id),
    KEY idx_wpe_player (player_id),
    KEY idx_wpe_club (club_id),
    FOREIGN KEY (club_id)   REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id)  REFERENCES water_polo_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES water_polo_players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sport_water_polo_match_stats (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id          INT UNSIGNED NOT NULL,
    club_id           INT UNSIGNED NOT NULL,
    team_side         ENUM('home','away') NOT NULL,
    goals             INT DEFAULT 0,
    exclusions        INT DEFAULT 0,
    exclusion_seconds INT DEFAULT 0,
    saves             INT DEFAULT 0,
    steals            INT DEFAULT 0,
    penalties_for     INT DEFAULT 0,
    UNIQUE KEY uniq_wp_stats (match_id, team_side),
    KEY idx_wpms_match (match_id),
    KEY idx_wpms_club (club_id),
    FOREIGN KEY (match_id) REFERENCES water_polo_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id)  REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
