-- Futsal: per-team agregaty meczowe.
-- Wymaga futsal_matches z 001_futsal.sql.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS sport_futsal_match_stats (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id        INT UNSIGNED NOT NULL,
    club_id         INT UNSIGNED NOT NULL,
    team_side       ENUM('home','away') NOT NULL,
    goals           INT DEFAULT 0,
    shots_total     INT DEFAULT 0,
    shots_on_target INT DEFAULT 0,
    fouls           INT DEFAULT 0,
    yellow_cards    INT DEFAULT 0,
    red_cards       INT DEFAULT 0,
    blue_cards      INT DEFAULT 0 COMMENT '2min penalty',
    team_fouls      INT DEFAULT 0 COMMENT '5-foul team penalty rule',
    saves_gk        INT DEFAULT 0,
    UNIQUE KEY uniq_futsal_stats (match_id, team_side),
    KEY idx_fsms_match (match_id),
    KEY idx_fsms_club (club_id),
    FOREIGN KEY (match_id) REFERENCES futsal_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id)  REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
