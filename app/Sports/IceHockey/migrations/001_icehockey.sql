-- Ice Hockey module (PZHL)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS icehockey_teams (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(150) NOT NULL,
    age_group  VARCHAR(50),
    arena      VARCHAR(150),
    coach_id   INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_iht_club` (`club_id`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS icehockey_players (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    team_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    jersey_number  SMALLINT UNSIGNED,
    position       ENUM('bramkarz','obrońca','napastnik') DEFAULT 'napastnik',
    shoots         ENUM('prawy','lewy') DEFAULT 'prawy',
    is_captain     TINYINT(1) NOT NULL DEFAULT 0,
    is_assistant   TINYINT(1) NOT NULL DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_ihp` (`team_id`, `member_id`),
    KEY `idx_ihp_club` (`club_id`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES icehockey_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS icehockey_matches (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id       INT UNSIGNED NOT NULL,
    home_team_id  INT UNSIGNED NOT NULL,
    away_team_name VARCHAR(150) NULL,
    match_date    DATETIME NOT NULL,
    arena         VARCHAR(200),
    p1_home       SMALLINT UNSIGNED DEFAULT 0,
    p1_away       SMALLINT UNSIGNED DEFAULT 0,
    p2_home       SMALLINT UNSIGNED DEFAULT 0,
    p2_away       SMALLINT UNSIGNED DEFAULT 0,
    p3_home       SMALLINT UNSIGNED DEFAULT 0,
    p3_away       SMALLINT UNSIGNED DEFAULT 0,
    ot_home       SMALLINT UNSIGNED DEFAULT 0,
    ot_away       SMALLINT UNSIGNED DEFAULT 0,
    so_home       SMALLINT UNSIGNED DEFAULT 0,
    so_away       SMALLINT UNSIGNED DEFAULT 0,
    shootout      TINYINT(1) NOT NULL DEFAULT 0,
    status        ENUM('zaplanowany','w_trakcie','zakończony','odwołany') DEFAULT 'zaplanowany',
    notes         TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ihm_club` (`club_id`),
    KEY `idx_ihm_date` (`match_date`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (home_team_id) REFERENCES icehockey_teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS icehockey_events (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    match_id     INT UNSIGNED NOT NULL,
    player_id    INT UNSIGNED NOT NULL,
    event_type   ENUM('gol','asysta','kara_2','kara_5','kara_10','kara_mecz','gol_pp','gol_sh','gol_en') NOT NULL,
    period       TINYINT UNSIGNED DEFAULT 1,
    minute       TINYINT UNSIGNED NULL,
    second       TINYINT UNSIGNED NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ihe_club`  (`club_id`),
    KEY `idx_ihe_match` (`match_id`),
    KEY `idx_ihe_player`(`player_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id)  REFERENCES icehockey_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES icehockey_players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
