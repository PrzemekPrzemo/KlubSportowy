-- Rugby (PZRugby)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS rugby_teams (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(150) NOT NULL,
    category   ENUM('senior_m','senior_k','junior_m','junior_k','U18','U16','U14','dzieci') DEFAULT 'senior_m',
    format     ENUM('15s','7s','touch') DEFAULT '15s',
    age_group  VARCHAR(50),
    coach_id   INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_rt_club` (`club_id`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rugby_players (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    team_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    jersey_number  SMALLINT UNSIGNED NULL,
    position       ENUM('filar','hooker','młynarz','flanker','numer_8','łącznik_młyna','łącznik_ataku','środkowy','skrzydłowy','pełny','uniwersalny') DEFAULT 'uniwersalny',
    is_captain     TINYINT(1) NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_rp` (`team_id`, `member_id`),
    KEY `idx_rp_club` (`club_id`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES rugby_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rugby_matches (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    home_team_id   INT UNSIGNED NOT NULL,
    away_team_name VARCHAR(150) NULL,
    match_date     DATETIME NOT NULL,
    location       VARCHAR(200) NULL,
    home_score     SMALLINT UNSIGNED DEFAULT 0,
    away_score     SMALLINT UNSIGNED DEFAULT 0,
    format         ENUM('15s','7s','touch') DEFAULT '15s',
    status         ENUM('zaplanowany','w_trakcie','zakończony','odwołany') DEFAULT 'zaplanowany',
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_rm_club` (`club_id`),
    KEY `idx_rm_date` (`match_date`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (home_team_id) REFERENCES rugby_teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rugby_events (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    match_id     INT UNSIGNED NOT NULL,
    player_id    INT UNSIGNED NOT NULL,
    event_type   ENUM('przyłożenie','podwyższenie','karny','drop','żółta','czerwona') NOT NULL,
    points       TINYINT UNSIGNED DEFAULT 0,
    minute       SMALLINT UNSIGNED NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_re_match` (`match_id`),
    KEY `idx_re_player`(`player_id`),
    KEY `idx_re_club`  (`club_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id)  REFERENCES rugby_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES rugby_players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
