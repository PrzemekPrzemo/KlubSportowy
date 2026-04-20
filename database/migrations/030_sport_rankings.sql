-- Generic sport ranking/points tracking per season

CREATE TABLE IF NOT EXISTS sport_rankings (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id           INT UNSIGNED NOT NULL,
    member_id         INT UNSIGNED NOT NULL,
    sport_key         VARCHAR(40) NOT NULL,
    season            VARCHAR(10) NOT NULL COMMENT 'e.g. 2024, 2024/25',
    ranking_points    INT NOT NULL DEFAULT 0,
    ranking_position  SMALLINT UNSIGNED,
    competitions_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    wins              TINYINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_member_sport_season (club_id, member_id, sport_key, season),
    INDEX idx_sport_season (club_id, sport_key, season),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
