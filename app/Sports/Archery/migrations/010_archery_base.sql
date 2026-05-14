-- Archery precision-sport base migration
-- Rundy strzeleckie + wyniki (oddzielnie od istniejacych tabel archery_bows/archery_scores).
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `archery_rounds` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    distance_m INT UNSIGNED NOT NULL,
    arrows_count INT UNSIGNED NOT NULL,
    bow_type ENUM('recurve','compound','barebow','traditional') NOT NULL,
    target_face_cm INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `archery_scores` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    round_id INT UNSIGNED NOT NULL,
    total_score INT UNSIGNED NOT NULL,
    arrows_detail JSON NULL COMMENT 'tablica wynikow per strzal: [10,9,X,8,...]',
    inner_tens INT UNSIGNED NULL COMMENT 'liczba X',
    place INT UNSIGNED NULL,
    is_competition TINYINT(1) NOT NULL DEFAULT 0,
    recorded_at DATETIME NOT NULL,
    KEY idx_member_round (member_id, round_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (round_id) REFERENCES archery_rounds(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
