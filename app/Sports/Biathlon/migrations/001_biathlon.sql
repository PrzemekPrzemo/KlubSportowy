-- Biathlon (PZBiathlon)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS biathlon_results (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    format         ENUM('sprint','indywidualny','pościg','masowy','sztafeta','mikst','super_sprint') NOT NULL DEFAULT 'sprint',
    event_name     VARCHAR(200) NOT NULL,
    event_date     DATE NOT NULL,
    venue          VARCHAR(200) NULL,
    category       VARCHAR(50) NULL,
    distance_km    DECIMAL(5,2) NOT NULL,
    run_time_s     INT UNSIGNED NULL,
    shootings_total INT UNSIGNED NULL COMMENT 'liczba strzałów oddanych',
    misses_total   INT UNSIGNED NULL COMMENT 'liczba pudeł',
    penalty_laps   INT UNSIGNED DEFAULT 0,
    penalty_time_s INT UNSIGNED DEFAULT 0,
    total_time_s   INT UNSIGNED NULL COMMENT 'czas biegu + kary',
    place          SMALLINT UNSIGNED NULL,
    fis_points     DECIMAL(6,2) NULL,
    dnf            TINYINT(1) NOT NULL DEFAULT 0,
    dns            TINYINT(1) NOT NULL DEFAULT 0,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_bir_club`   (`club_id`),
    KEY `idx_bir_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
