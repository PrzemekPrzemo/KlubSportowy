-- Field Hockey (PZHnT)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS field_hockey_teams (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(150) NOT NULL,
    category   ENUM('senior_m','senior_k','junior_m','junior_k','U18','U16','U14','dzieci') DEFAULT 'senior_m',
    coach_id   INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_fht_club` (`club_id`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS field_hockey_players (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    team_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    jersey_number  SMALLINT UNSIGNED NULL,
    position       ENUM('bramkarz','obrońca','pomocnik','atakujący','uniwersalny') DEFAULT 'uniwersalny',
    is_captain     TINYINT(1) NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_fhp` (`team_id`, `member_id`),
    KEY `idx_fhp_club` (`club_id`),
    FOREIGN KEY (club_id)  REFERENCES clubs(id)              ON DELETE CASCADE,
    FOREIGN KEY (team_id)  REFERENCES field_hockey_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id)           ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS field_hockey_matches (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    home_team_id   INT UNSIGNED NOT NULL,
    away_team_name VARCHAR(150) NULL,
    match_date     DATETIME NOT NULL,
    location       VARCHAR(200) NULL,
    home_score     SMALLINT UNSIGNED DEFAULT 0,
    away_score     SMALLINT UNSIGNED DEFAULT 0,
    status         ENUM('zaplanowany','w_trakcie','zakończony','odwołany') DEFAULT 'zaplanowany',
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_fhm_club` (`club_id`),
    KEY `idx_fhm_date` (`match_date`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (home_team_id) REFERENCES field_hockey_teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS field_hockey_events (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    match_id     INT UNSIGNED NOT NULL,
    player_id    INT UNSIGNED NOT NULL,
    event_type   ENUM('gol','asysta','PC','PS','żółta','zielona','czerwona') NOT NULL COMMENT 'PC=penalty corner, PS=penalty stroke',
    minute       SMALLINT UNSIGNED NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_fhe_match` (`match_id`),
    KEY `idx_fhe_player`(`player_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id)  REFERENCES field_hockey_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES field_hockey_players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
