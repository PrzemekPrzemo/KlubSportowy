-- Cross-country Ski (PZN XC)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS xc_ski_results (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    technique    ENUM('classic','skate','pościg','masowy','sprint_classic','sprint_skate') NOT NULL DEFAULT 'classic',
    distance_km  DECIMAL(6,2) NOT NULL,
    event_name   VARCHAR(200) NOT NULL,
    event_date   DATE NOT NULL,
    venue        VARCHAR(200) NULL,
    category     VARCHAR(50) NULL,
    time_s       INT UNSIGNED NULL,
    place        SMALLINT UNSIGNED NULL,
    fis_points   DECIMAL(6,2) NULL,
    dnf          TINYINT(1) NOT NULL DEFAULT 0,
    dns          TINYINT(1) NOT NULL DEFAULT 0,
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_xcr_club`   (`club_id`),
    KEY `idx_xcr_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
