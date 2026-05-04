-- Figure Skating (PZLF)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS figure_skating_results (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    discipline   ENUM('singles_m','singles_w','pairs','ice_dance','synchro') NOT NULL,
    level        ENUM('novice','junior','senior','adult','masters') NOT NULL DEFAULT 'senior',
    event_name   VARCHAR(200) NOT NULL,
    event_date   DATE NOT NULL,
    venue        VARCHAR(200) NULL,
    category     VARCHAR(50) NULL,
    partner_name VARCHAR(200) NULL COMMENT 'dla pair/ice_dance',
    sp_tes       DECIMAL(6,2) NULL COMMENT 'Short Program Technical Element Score',
    sp_pcs       DECIMAL(6,2) NULL COMMENT 'Short Program Components',
    sp_total     DECIMAL(6,2) NULL,
    fs_tes       DECIMAL(6,2) NULL COMMENT 'Free Skating TES',
    fs_pcs       DECIMAL(6,2) NULL COMMENT 'Free Skating PCS',
    fs_total     DECIMAL(6,2) NULL,
    total_score  DECIMAL(7,2) NULL COMMENT 'SP + FS - deductions',
    place        SMALLINT UNSIGNED NULL,
    deductions   DECIMAL(5,2) DEFAULT 0,
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_fsr_club`   (`club_id`),
    KEY `idx_fsr_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
