-- Sailing individual-sport base migration
-- Lodzie + wyniki regat.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `sailing_boats` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    boat_class VARCHAR(80) NOT NULL COMMENT 'np. Optimist/Laser/420/470/49er/Finn',
    sail_number VARCHAR(20) NULL,
    crew_size TINYINT UNSIGNED NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_club_class (club_id, boat_class),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sailing_regatta_results` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL COMMENT 'skipper',
    boat_id INT UNSIGNED NULL,
    regatta_name VARCHAR(200) NOT NULL,
    boat_class VARCHAR(80) NOT NULL,
    race_count TINYINT UNSIGNED NULL,
    race_results JSON NULL COMMENT 'tablica miejsc per wyscig [1,3,2,DSQ,...]',
    overall_place INT UNSIGNED NULL,
    total_points DECIMAL(8,2) NULL COMMENT 'low-point scoring',
    competed_at DATE NOT NULL,
    KEY idx_member (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (boat_id) REFERENCES sailing_boats(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
