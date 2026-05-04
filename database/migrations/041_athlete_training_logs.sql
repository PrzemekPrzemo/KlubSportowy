-- M3: Dziennik treningowy zawodnika (self-log z portalu)
-- Dla: cycling (power), swimming (intervals), weightlifting (volume),
--      triathlon (3-sport cross-training), climbing, biegacze

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS athlete_training_logs (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id       INT UNSIGNED NOT NULL,
    member_id     INT UNSIGNED NOT NULL,
    log_date      DATE NOT NULL,
    session_type  ENUM('trening','zawody','regeneracja','sparing','test') NOT NULL DEFAULT 'trening',
    sport_key     VARCHAR(50) NULL COMMENT 'klucz sportu z manifestu — opcjonalny, gdy trening ogólny',
    duration_min  SMALLINT UNSIGNED NULL,
    distance_km   DECIMAL(6,1) NULL,
    volume_kg     INT UNSIGNED NULL COMMENT 'suma podnoszonej wagi — dla weightliftingu',
    intensity     TINYINT UNSIGNED NULL COMMENT 'RPE 1-10',
    avg_hr        SMALLINT UNSIGNED NULL,
    max_hr        SMALLINT UNSIGNED NULL,
    avg_power_w   SMALLINT UNSIGNED NULL COMMENT 'średnia moc (W) — dla cyclingu',
    notes         TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_atl_member_date` (`member_id`, `log_date`),
    KEY `idx_atl_club`         (`club_id`),
    KEY `idx_atl_sport`        (`sport_key`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
