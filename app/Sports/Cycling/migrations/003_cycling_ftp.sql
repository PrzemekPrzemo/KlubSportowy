-- Cycling: testy FTP + profil atlety
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS cycling_athletes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id             INT UNSIGNED NOT NULL,
    member_id           INT UNSIGNED NOT NULL,
    uci_id              VARCHAR(30) NULL,
    primary_discipline  ENUM('szosowe','torowe','mtb','bmx','przełajowe','gravel','trialowe') DEFAULT 'szosowe',
    weight_kg           DECIMAL(4,1) NULL,
    ftp_watts           SMALLINT UNSIGNED NULL,
    ftp_updated_at      DATE NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_ca` (`club_id`, `member_id`),
    KEY `idx_ca_club` (`club_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cycling_ftp_tests (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id       INT UNSIGNED NOT NULL,
    member_id     INT UNSIGNED NOT NULL,
    test_date     DATE NOT NULL,
    ftp_watts     SMALLINT UNSIGNED NOT NULL,
    protocol      ENUM('20min','ramp','8min','60min','field') NOT NULL DEFAULT '20min',
    weight_kg     DECIMAL(4,1) NULL,
    notes         TEXT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_cf_club`   (`club_id`),
    KEY `idx_cf_member` (`member_id`),
    KEY `idx_cf_date`   (`test_date`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
