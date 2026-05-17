-- Curling: team/match layer (uzupelnienie do winter_base z 010_winter_base.sql).
-- Druzyny 4-osobowe, scoring per-end, hammer alternation.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS curling_teams (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(150) NOT NULL,
    category   ENUM('senior_m','senior_k','mixed','mixed_doubles','wheelchair','junior') DEFAULT 'mixed',
    coach_id   INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ct_club (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curling_players (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id     INT UNSIGNED NOT NULL,
    team_id     INT UNSIGNED NOT NULL,
    member_id   INT UNSIGNED NOT NULL,
    position    ENUM('skip','third','second','lead','alternate') DEFAULT 'lead',
    is_captain  TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cp (team_id, member_id),
    KEY idx_cp_club (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES curling_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS curling_matches (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    home_team_id   INT UNSIGNED NOT NULL,
    away_team_name VARCHAR(150) NULL,
    match_date     DATETIME NOT NULL,
    location       VARCHAR(200) NULL,
    home_score     SMALLINT UNSIGNED DEFAULT 0,
    away_score     SMALLINT UNSIGNED DEFAULT 0,
    ends_planned   TINYINT UNSIGNED DEFAULT 8,
    hammer_start   ENUM('home','away') DEFAULT 'away' COMMENT 'kto ma hammer w 1. endzie',
    status         ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') DEFAULT 'zaplanowany',
    notes          TEXT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cm_club (club_id),
    KEY idx_cm_date (match_date),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (home_team_id) REFERENCES curling_teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sport_curling_match_ends (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id    INT UNSIGNED NOT NULL,
    club_id     INT UNSIGNED NOT NULL,
    end_number  TINYINT UNSIGNED NOT NULL,
    home_score  TINYINT UNSIGNED DEFAULT 0,
    away_score  TINYINT UNSIGNED DEFAULT 0,
    hammer_side ENUM('home','away') NOT NULL,
    UNIQUE KEY uniq_match_end (match_id, end_number),
    KEY idx_cme_club (club_id),
    FOREIGN KEY (match_id) REFERENCES curling_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id)  REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
