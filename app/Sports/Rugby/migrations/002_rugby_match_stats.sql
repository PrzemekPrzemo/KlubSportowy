-- Rugby: per-team agregaty meczowe.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS sport_rugby_match_scoring (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id      INT UNSIGNED NOT NULL,
    club_id       INT UNSIGNED NOT NULL,
    team_side     ENUM('home','away') NOT NULL,
    tries         INT DEFAULT 0,
    conversions   INT DEFAULT 0,
    penalties     INT DEFAULT 0,
    drop_goals    INT DEFAULT 0,
    cards_yellow  INT DEFAULT 0,
    cards_red     INT DEFAULT 0,
    UNIQUE KEY uniq_rugby_stats (match_id, team_side),
    KEY idx_rms_match (match_id),
    KEY idx_rms_club (club_id),
    FOREIGN KEY (match_id) REFERENCES rugby_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id)  REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
