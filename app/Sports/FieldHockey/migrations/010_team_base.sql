-- FieldHockey team-sports base migration
-- Wspolne tabele dla sportow druzynowych: druzyny klubowe, sklady, mecze, statystyki graczy.
SET foreign_key_checks = 0;

-- Druzyny per klub
CREATE TABLE IF NOT EXISTS `field_hockey_teams` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    age_category VARCHAR(40) NULL,
    gender ENUM('M','F','mixed') NOT NULL DEFAULT 'mixed',
    league VARCHAR(80) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_club_active (club_id, is_active),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Przypisanie zawodnikow do druzyn
CREATE TABLE IF NOT EXISTS `field_hockey_team_members` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    position VARCHAR(40) NULL,
    jersey_number TINYINT UNSIGNED NULL,
    joined_at DATE NULL,
    left_at DATE NULL,
    UNIQUE KEY uniq_team_member (team_id, member_id),
    KEY idx_member (member_id),
    FOREIGN KEY (team_id) REFERENCES `field_hockey_teams`(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mecze
CREATE TABLE IF NOT EXISTS `field_hockey_matches` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    tournament_id INT UNSIGNED NULL,
    home_team_id INT UNSIGNED NOT NULL,
    away_team_name VARCHAR(120) NOT NULL COMMENT 'moze byc z innego klubu — string, nie FK',
    away_team_id INT UNSIGNED NULL COMMENT 'jesli z tego samego klubu',
    venue VARCHAR(200) NULL,
    played_at DATETIME NULL,
    home_score INT UNSIGNED NULL,
    away_score INT UNSIGNED NULL,
    periods JSON NULL COMMENT 'np. [[1,0],[2,1]] tercje/kwarty',
    status ENUM('scheduled','live','finished','cancelled') NOT NULL DEFAULT 'scheduled',
    notes TEXT NULL,
    KEY idx_club_date (club_id, played_at),
    KEY idx_home_team (home_team_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (home_team_id) REFERENCES `field_hockey_teams`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statystyki gracza w meczu
CREATE TABLE IF NOT EXISTS `field_hockey_match_stats` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    goals INT UNSIGNED NOT NULL DEFAULT 0,
    assists INT UNSIGNED NOT NULL DEFAULT 0,
    yellow_cards TINYINT UNSIGNED NOT NULL DEFAULT 0,
    red_cards TINYINT UNSIGNED NOT NULL DEFAULT 0,
    minutes_played SMALLINT UNSIGNED NULL,
    extra JSON NULL COMMENT 'sport-specific stats (saves, shots, fouls, etc.)',
    UNIQUE KEY uniq_match_member (match_id, member_id),
    FOREIGN KEY (match_id) REFERENCES `field_hockey_matches`(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
