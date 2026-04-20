-- Aikido module migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS aikido_belts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    belt_level   ENUM('6kyu','5kyu','4kyu','3kyu','2kyu','1kyu','1dan','2dan','3dan','4dan','5dan','6dan','7dan','8dan') NOT NULL,
    granted_date DATE NOT NULL,
    examiner     VARCHAR(120),
    location     VARCHAR(150),
    style        ENUM('aikikai','ki_society','yoshinkan','iwama','tomiki') DEFAULT 'aikikai',
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aikido_results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    competition_name VARCHAR(200) NOT NULL,
    competition_date DATE NOT NULL,
    category         ENUM('taigi','randori','tanto_randori','embu') NOT NULL DEFAULT 'embu',
    age_category     VARCHAR(50),
    placement        TINYINT UNSIGNED,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
