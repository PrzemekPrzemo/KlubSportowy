-- Swimming module migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS swimming_results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    competition_name VARCHAR(200),
    score_date       DATE NOT NULL,
    stroke           ENUM('freestyle','backstroke','breaststroke','butterfly','medley','relay_freestyle','relay_medley') NOT NULL,
    distance_m       SMALLINT UNSIGNED NOT NULL,
    pool_type        ENUM('25m','50m','open_water') NOT NULL DEFAULT '25m',
    time_ms          INT UNSIGNED NOT NULL COMMENT 'time in milliseconds',
    age_category     VARCHAR(50),
    placement        TINYINT UNSIGNED,
    personal_best    TINYINT(1) NOT NULL DEFAULT 0,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sw_club`   (`club_id`),
    KEY `idx_sw_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
