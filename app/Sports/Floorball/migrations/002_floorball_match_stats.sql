-- Floorball: per-team agregaty meczowe.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS sport_floorball_match_stats (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id           INT UNSIGNED NOT NULL,
    club_id            INT UNSIGNED NOT NULL,
    team_side          ENUM('home','away') NOT NULL,
    goals              INT DEFAULT 0,
    shots_total        INT DEFAULT 0,
    saves              INT DEFAULT 0,
    penalties_2min     INT DEFAULT 0,
    penalties_10min    INT DEFAULT 0,
    power_play_goals   INT DEFAULT 0,
    short_handed_goals INT DEFAULT 0,
    UNIQUE KEY uniq_fb_stats (match_id, team_side),
    KEY idx_fbms_match (match_id),
    KEY idx_fbms_club (club_id),
    FOREIGN KEY (match_id) REFERENCES floorball_matches(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id)  REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
