-- M1: Body metrics — pomiary antropometryczne zawodnika (historia)
-- Dla: weightlifting (Sinclair), cycling (W/kg), boxing (weight class),
--      BJJ, wrestling, handball, taekwondo, climbing

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS body_metrics (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    measured_at    DATE NOT NULL,
    weight_kg      DECIMAL(5,2) NULL,
    height_cm      SMALLINT UNSIGNED NULL,
    body_fat_pct   DECIMAL(4,1) NULL,
    resting_hr     SMALLINT UNSIGNED NULL COMMENT 'tętno spoczynkowe bpm',
    wingspan_cm    SMALLINT UNSIGNED NULL COMMENT 'rozpiętość ramion — ważna dla wspinaczki/boksu',
    measured_by    VARCHAR(150) NULL,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_bm_club_member` (`club_id`, `member_id`),
    KEY `idx_bm_date`        (`measured_at`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
