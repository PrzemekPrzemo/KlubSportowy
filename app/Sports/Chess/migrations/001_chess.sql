-- Chess module migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS chess_ratings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    rating_type  ENUM('fide_classical','fide_rapid','fide_blitz','pzszach','elo_internal') NOT NULL DEFAULT 'fide_classical',
    rating       SMALLINT UNSIGNED NOT NULL,
    rating_date  DATE NOT NULL,
    notes        VARCHAR(200),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chess_results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    competition_name VARCHAR(200) NOT NULL,
    competition_date DATE NOT NULL,
    opponent_name    VARCHAR(150),
    result           ENUM('win','draw','loss') NOT NULL,
    color            ENUM('white','black') DEFAULT NULL,
    opening          VARCHAR(80) COMMENT 'ECO code or opening name',
    category         ENUM('classical','rapid','blitz','bullet','correspondence') DEFAULT 'classical',
    tournament_round VARCHAR(20),
    placement        TINYINT UNSIGNED COMMENT 'final tournament placement',
    rating_change    SMALLINT COMMENT 'ELO change from this game/tournament',
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
