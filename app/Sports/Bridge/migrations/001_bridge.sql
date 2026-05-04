-- Bridge (PZBS)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS bridge_partnerships (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    player1_id   INT UNSIGNED NOT NULL,
    player2_id   INT UNSIGNED NOT NULL,
    name         VARCHAR(150) NULL,
    category     ENUM('open','kobiety','mixed','juniorzy','seniorzy') DEFAULT 'open',
    active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_bp` (`club_id`, `player1_id`, `player2_id`),
    KEY `idx_bp_club` (`club_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (player1_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (player2_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bridge_tournaments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id         INT UNSIGNED NOT NULL,
    name            VARCHAR(200) NOT NULL,
    tournament_type ENUM('para','team','indywidualny','mikst','inny') NOT NULL DEFAULT 'para',
    tournament_date DATE NOT NULL,
    location        VARCHAR(200) NULL,
    partnership_id  INT UNSIGNED NULL,
    member_id       INT UNSIGNED NULL COMMENT 'dla turniejów indywidualnych',
    place           SMALLINT UNSIGNED NULL,
    score_mp        DECIMAL(7,2) NULL COMMENT 'matchpoints',
    score_imp       DECIMAL(7,2) NULL COMMENT 'IMP score',
    pzbs_points     DECIMAL(6,2) NULL COMMENT 'punkty rankingowe PZBS',
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_bt_club` (`club_id`),
    FOREIGN KEY (club_id)        REFERENCES clubs(id)              ON DELETE CASCADE,
    FOREIGN KEY (partnership_id) REFERENCES bridge_partnerships(id) ON DELETE SET NULL,
    FOREIGN KEY (member_id)      REFERENCES members(id)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
