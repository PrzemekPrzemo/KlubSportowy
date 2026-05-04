-- Kickboxing (PZKick)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS kickboxing_belts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id     INT UNSIGNED NOT NULL,
    member_id   INT UNSIGNED NOT NULL,
    belt_color  ENUM('biały','żółty','pomarańczowy','zielony','niebieski','fioletowy','brązowy','czerwony','czarny') NOT NULL DEFAULT 'biały',
    dan         TINYINT UNSIGNED DEFAULT 0,
    exam_date   DATE NOT NULL,
    examiner    VARCHAR(150) NULL,
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_kb_member` (`member_id`),
    KEY `idx_kb_club`   (`club_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kickboxing_results (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    style          ENUM('low_kick','K1','light_contact','kickboxing_punktowy','muay_thai_polskie','full_contact') NOT NULL,
    event_name     VARCHAR(200) NOT NULL,
    event_date     DATE NOT NULL,
    venue          VARCHAR(200) NULL,
    opponent_name  VARCHAR(150) NULL,
    weight_class   VARCHAR(30) NULL,
    result         ENUM('W','L','D','NC','DQ') NULL,
    method         ENUM('KO','TKO','points','decision','DQ','NC') NULL,
    rounds_total   TINYINT UNSIGNED NULL,
    rounds_fought  TINYINT UNSIGNED NULL,
    amateur        TINYINT(1) NOT NULL DEFAULT 1,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_kbr_club`   (`club_id`),
    KEY `idx_kbr_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
