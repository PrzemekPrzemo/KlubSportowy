-- Chess mind-games base migration
-- Wspolne tabele dla sportow umyslowych: ratingi, partie.
SET foreign_key_checks = 0;

-- Rating ELO dla zawodnika
CREATE TABLE IF NOT EXISTS `chess_ratings` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    rating INT UNSIGNED NOT NULL DEFAULT 1200,
    rating_type VARCHAR(40) NOT NULL DEFAULT 'club' COMMENT 'club/national/fide',
    games_played INT UNSIGNED NOT NULL DEFAULT 0,
    title VARCHAR(20) NULL COMMENT 'np. CM/FM/IM/GM dla szachow',
    last_updated DATETIME NULL,
    UNIQUE KEY uniq_member_type (member_id, rating_type),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partie 1v1
CREATE TABLE IF NOT EXISTS `chess_games` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    tournament_id INT UNSIGNED NULL,
    player_a_id INT UNSIGNED NOT NULL,
    player_b_id INT UNSIGNED NOT NULL,
    partner_a_id INT UNSIGNED NULL,
    partner_b_id INT UNSIGNED NULL,
    result ENUM('A','B','draw') NULL,
    result_detail VARCHAR(40) NULL COMMENT 'np. 1-0/1/2-1/2/0-1',
    moves_count SMALLINT UNSIGNED NULL,
    duration_seconds INT UNSIGNED NULL,
    pgn TEXT NULL COMMENT 'Portable Game Notation',
    played_at DATETIME NULL,
    KEY idx_player_a (player_a_id),
    KEY idx_player_b (player_b_id),
    KEY idx_club_date (club_id, played_at),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (player_a_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (player_b_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
