CREATE TABLE IF NOT EXISTS floorball_teams (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(120) NOT NULL,
    age_group  VARCHAR(50) DEFAULT NULL,
    coach_id   INT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS floorball_players (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    team_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    jersey_number  TINYINT UNSIGNED DEFAULT NULL,
    position       ENUM('bramkarz','obrońca','napastnik') NOT NULL DEFAULT 'napastnik',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_team_member (team_id, member_id),
    INDEX (club_id),
    INDEX (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES floorball_teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS floorball_matches (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    home_team_id   INT UNSIGNED DEFAULT NULL,
    away_team_id   INT UNSIGNED DEFAULT NULL,
    home_score     TINYINT UNSIGNED DEFAULT NULL,
    away_score     TINYINT UNSIGNED DEFAULT NULL,
    match_date     DATETIME NOT NULL,
    location       VARCHAR(150) DEFAULT NULL,
    status         ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') NOT NULL DEFAULT 'zaplanowany',
    notes          TEXT DEFAULT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (home_team_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS floorball_events (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    match_id   INT UNSIGNED NOT NULL,
    player_id  INT UNSIGNED NOT NULL,
    event_type ENUM('gol','asysta','kara_2min','kara_10min','gol_pp','gol_sh') NOT NULL DEFAULT 'gol',
    minute     TINYINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (match_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES floorball_matches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
