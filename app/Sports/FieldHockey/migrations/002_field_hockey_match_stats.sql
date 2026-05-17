-- Field Hockey: per-team agregaty meczowe.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS sport_field_hockey_match_stats (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id         INT UNSIGNED NOT NULL,
    club_id          INT UNSIGNED NOT NULL,
    team_side        ENUM('home','away') NOT NULL,
    goals            INT DEFAULT 0,
    penalty_corners  INT DEFAULT 0,
    penalty_strokes  INT DEFAULT 0,
    shots_total      INT DEFAULT 0,
    saves            INT DEFAULT 0,
    cards_green      INT DEFAULT 0,
    cards_yellow     INT DEFAULT 0,
    cards_red        INT DEFAULT 0,
    UNIQUE KEY uniq_fh_stats (match_id, team_side),
    KEY idx_fhms_match (match_id),
    KEY idx_fhms_club (club_id),
    FOREIGN KEY (match_id) REFERENCES field_hockey_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id)  REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
