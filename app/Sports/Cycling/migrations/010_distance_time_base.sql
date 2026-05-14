-- Cycling distance/time base migration
-- Wspolne tabele dla sportow czasowo-dystansowych: dyscypliny, wyniki, rekordy osobiste.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `cycling_disciplines` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    distance_m INT UNSIGNED NULL COMMENT 'NULL dla wariantow bez ustalonego dystansu',
    discipline_type VARCHAR(40) NULL COMMENT 'road/track/mtb/cyclocross/bmx',
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cycling_results` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    discipline_id INT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NULL,
    time_ms INT UNSIGNED NULL COMMENT 'overall time',
    distance_completed_m INT UNSIGNED NULL,
    place INT UNSIGNED NULL,
    splits JSON NULL COMMENT 'splity per okrazenie/segment',
    is_competition TINYINT(1) NOT NULL DEFAULT 0,
    recorded_at DATETIME NOT NULL,
    notes VARCHAR(255) NULL,
    KEY idx_member_disc (member_id, discipline_id),
    KEY idx_club_date (club_id, recorded_at),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (discipline_id) REFERENCES `cycling_disciplines`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cycling_personal_records` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    discipline_id INT UNSIGNED NOT NULL,
    time_ms INT UNSIGNED NOT NULL,
    achieved_at DATE NOT NULL,
    UNIQUE KEY uniq_member_disc (member_id, discipline_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (discipline_id) REFERENCES `cycling_disciplines`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
