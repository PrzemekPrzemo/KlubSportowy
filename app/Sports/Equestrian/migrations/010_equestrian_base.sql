-- Equestrian individual-sport base migration
-- Konie + wyniki zawodow jezdzieckich.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `equestrian_horses` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    breed VARCHAR(80) NULL,
    birth_date DATE NULL,
    gender ENUM('mare','stallion','gelding') NULL,
    passport_number VARCHAR(80) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    KEY idx_club (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `equestrian_results` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    rider_id INT UNSIGNED NOT NULL,
    horse_id INT UNSIGNED NULL,
    discipline ENUM('dressage','show_jumping','eventing','cross_country','vaulting','reining') NOT NULL,
    competition_level VARCHAR(40) NULL,
    score DECIMAL(6,2) NULL,
    time_seconds INT UNSIGNED NULL,
    penalty_points INT UNSIGNED NULL,
    place INT UNSIGNED NULL,
    is_competition TINYINT(1) NOT NULL DEFAULT 0,
    competed_at DATETIME NOT NULL,
    KEY idx_rider (rider_id),
    KEY idx_horse (horse_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (rider_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (horse_id) REFERENCES equestrian_horses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
