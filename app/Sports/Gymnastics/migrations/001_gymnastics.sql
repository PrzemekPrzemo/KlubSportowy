-- Gymnastics results
CREATE TABLE IF NOT EXISTS gymnastics_results (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id           INT UNSIGNED NOT NULL,
    member_id         INT UNSIGNED NOT NULL,
    discipline        ENUM('artystyczna','rytmiczna','akrobatyczna','trampolina') NOT NULL DEFAULT 'artystyczna',
    apparatus         VARCHAR(100) DEFAULT NULL,
    event_name        VARCHAR(200) NOT NULL,
    event_date        DATE NOT NULL,
    difficulty_score  DECIMAL(5,3) NOT NULL DEFAULT 0.000,
    execution_score   DECIMAL(5,3) NOT NULL DEFAULT 0.000,
    penalty_score     DECIMAL(5,3) NOT NULL DEFAULT 0.000,
    total_score       DECIMAL(6,3) GENERATED ALWAYS AS (difficulty_score + execution_score - penalty_score) STORED,
    placement         TINYINT UNSIGNED DEFAULT NULL,
    age_category      VARCHAR(50) DEFAULT NULL,
    notes             TEXT DEFAULT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gymnastics minor consents (RODO for children)
CREATE TABLE IF NOT EXISTS gymnastics_minor_consents (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id         INT UNSIGNED NOT NULL,
    member_id       INT UNSIGNED NOT NULL,
    guardian_name   VARCHAR(120) NOT NULL DEFAULT '',
    guardian_phone  VARCHAR(30) DEFAULT NULL,
    photo_consent   TINYINT(1) NOT NULL DEFAULT 0,
    media_consent   TINYINT(1) NOT NULL DEFAULT 0,
    signed_date     DATE DEFAULT NULL,
    notes           TEXT DEFAULT NULL,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_member_club (member_id, club_id),
    INDEX (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
