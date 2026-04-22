-- Alpine Ski (PZN Alpine)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS alpine_ski_results (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    discipline   ENUM('slalom','slalom_gigant','supergigant','zjazd','kombinacja','kombinacja_alpejska') NOT NULL,
    event_name   VARCHAR(200) NOT NULL,
    event_date   DATE NOT NULL,
    venue        VARCHAR(200) NULL,
    category     VARCHAR(50) NULL,
    run1_ms      INT UNSIGNED NULL,
    run2_ms      INT UNSIGNED NULL,
    total_ms     INT UNSIGNED NULL,
    place        SMALLINT UNSIGNED NULL,
    fis_points   DECIMAL(6,2) NULL,
    dnf          TINYINT(1) NOT NULL DEFAULT 0,
    dns          TINYINT(1) NOT NULL DEFAULT 0,
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_asr_club`   (`club_id`),
    KEY `idx_asr_member` (`member_id`),
    KEY `idx_asr_disc`   (`discipline`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
