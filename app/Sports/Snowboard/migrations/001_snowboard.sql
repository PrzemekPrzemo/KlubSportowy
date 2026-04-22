-- Snowboard (PZN SB)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS snowboard_results (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    discipline   ENUM('slalom','gigant','halfpipe','slopestyle','big_air','boardercross','snowboardcross','parallel_slalom') NOT NULL,
    event_name   VARCHAR(200) NOT NULL,
    event_date   DATE NOT NULL,
    venue        VARCHAR(200) NULL,
    category     VARCHAR(50) NULL,
    run1_score   DECIMAL(6,2) NULL,
    run2_score   DECIMAL(6,2) NULL,
    best_score   DECIMAL(6,2) NULL,
    place        SMALLINT UNSIGNED NULL,
    fis_points   DECIMAL(6,2) NULL,
    dnf          TINYINT(1) NOT NULL DEFAULT 0,
    dns          TINYINT(1) NOT NULL DEFAULT 0,
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sbr_club`   (`club_id`),
    KEY `idx_sbr_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
