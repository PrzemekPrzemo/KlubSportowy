-- Ski Jumping (PZN SJ)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS ski_jump_results (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    event_name     VARCHAR(200) NOT NULL,
    event_date     DATE NOT NULL,
    venue          VARCHAR(200) NULL,
    hill_k         SMALLINT UNSIGNED NULL COMMENT 'punkt K skoczni, np. 125',
    hill_size      VARCHAR(20) NULL COMMENT 'np. HS140, HS100',
    category       VARCHAR(50) NULL,
    jump1_m        DECIMAL(5,1) NULL,
    jump1_points   DECIMAL(6,2) NULL,
    jump2_m        DECIMAL(5,1) NULL,
    jump2_points   DECIMAL(6,2) NULL,
    total_points   DECIMAL(7,2) NULL,
    place          SMALLINT UNSIGNED NULL,
    fis_points     DECIMAL(6,2) NULL,
    dnf            TINYINT(1) NOT NULL DEFAULT 0,
    dns            TINYINT(1) NOT NULL DEFAULT 0,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sjr_club`   (`club_id`),
    KEY `idx_sjr_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
