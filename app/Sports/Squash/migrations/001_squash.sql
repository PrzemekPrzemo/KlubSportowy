-- Squash module migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS squash_results (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id                 INT UNSIGNED NOT NULL,
    member_id               INT UNSIGNED NOT NULL,
    competition_name        VARCHAR(200),
    match_date              DATE NOT NULL,
    opponent_name           VARCHAR(150),
    category                ENUM('singles','doubles','mixed_doubles') NOT NULL DEFAULT 'singles',
    sets_won                TINYINT UNSIGNED,
    sets_lost               TINYINT UNSIGNED,
    games_detail            VARCHAR(100) COMMENT 'e.g. 11-5, 11-8, 11-9',
    psa_ranking_before      INT,
    psa_ranking_after       INT,
    competition_round       VARCHAR(30) COMMENT 'Final, Semifinal, QF, R16...',
    placement               TINYINT UNSIGNED,
    age_category            VARCHAR(50),
    notes                   TEXT,
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS squash_rankings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    season       VARCHAR(10) NOT NULL,
    psa_rating   INT DEFAULT 0,
    psa_position INT,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_member_season (club_id, member_id, season),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
