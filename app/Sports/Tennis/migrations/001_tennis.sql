-- Tennis module migration (PZT)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS tennis_matches (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    player1_id     INT UNSIGNED NOT NULL,
    player2_id     INT UNSIGNED NOT NULL,
    match_date     DATE NOT NULL,
    surface        ENUM('clay','hard','grass','indoor','carpet') NOT NULL DEFAULT 'hard',
    match_type     ENUM('rankingowy','towarzyski','turniejowy','treningowy') NOT NULL DEFAULT 'towarzyski',
    sets           VARCHAR(60) NOT NULL COMMENT 'format: 6:4,7:5,6:3',
    winner_id      INT UNSIGNED NULL,
    tournament     VARCHAR(150) NULL,
    duration_min   SMALLINT UNSIGNED NULL,
    notes          TEXT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_tm_club`     (`club_id`),
    KEY `idx_tm_p1`       (`player1_id`),
    KEY `idx_tm_p2`       (`player2_id`),
    KEY `idx_tm_date`     (`match_date`),
    FOREIGN KEY (club_id)    REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (player1_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (player2_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tennis_rankings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id         INT UNSIGNED NOT NULL,
    member_id       INT UNSIGNED NOT NULL,
    season          VARCHAR(10) NOT NULL COMMENT 'np. 2025',
    points          INT NOT NULL DEFAULT 0,
    matches_played  INT NOT NULL DEFAULT 0,
    wins            INT NOT NULL DEFAULT 0,
    losses          INT NOT NULL DEFAULT 0,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_tr` (`club_id`, `member_id`, `season`),
    KEY `idx_tr_club` (`club_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tennis_courts (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(100) NOT NULL,
    surface    ENUM('clay','hard','grass','indoor','carpet') NOT NULL DEFAULT 'hard',
    indoor     TINYINT(1) NOT NULL DEFAULT 0,
    active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_tc_club` (`club_id`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
