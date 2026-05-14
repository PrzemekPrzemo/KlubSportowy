-- Golf individual-sport base migration
-- Pola golfowe + rundy z wynikami.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `golf_courses` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    holes_count TINYINT UNSIGNED NOT NULL DEFAULT 18,
    par INT UNSIGNED NOT NULL,
    slope_rating SMALLINT UNSIGNED NULL,
    course_rating DECIMAL(4,1) NULL,
    location VARCHAR(200) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `golf_rounds` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    total_score INT UNSIGNED NOT NULL,
    to_par INT NOT NULL COMMENT '+/- vs par',
    holes_scores JSON NULL COMMENT 'wyniki per dolek [4,5,3,...]',
    handicap_used DECIMAL(4,1) NULL,
    is_competition TINYINT(1) NOT NULL DEFAULT 0,
    played_at DATETIME NOT NULL,
    KEY idx_member (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES golf_courses(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
