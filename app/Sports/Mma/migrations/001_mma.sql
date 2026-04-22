-- MMA (PZMMA)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS mma_fighters (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id         INT UNSIGNED NOT NULL,
    member_id       INT UNSIGNED NOT NULL,
    nickname        VARCHAR(100) NULL,
    stance          ENUM('ortodox','southpaw','switch') DEFAULT 'ortodox',
    weight_class    VARCHAR(30) NULL,
    primary_style   ENUM('boxing','wrestling','bjj','muay_thai','karate','sambo','judo','kickboxing','mixed') DEFAULT 'mixed',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_mf` (`club_id`, `member_id`),
    KEY `idx_mf_club` (`club_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mma_results (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    opponent_name  VARCHAR(150) NULL,
    event_name     VARCHAR(200) NOT NULL,
    event_date     DATE NOT NULL,
    venue          VARCHAR(200) NULL,
    result         ENUM('W','L','D','NC') NULL,
    method         ENUM('KO','TKO','submission','decision_unanimous','decision_split','decision_majority','DQ','NC') NULL,
    round          TINYINT UNSIGNED NULL,
    time_s         INT UNSIGNED NULL COMMENT 'sekundy w rundzie',
    weight_class   VARCHAR(30) NULL,
    amateur        TINYINT(1) NOT NULL DEFAULT 1,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_mr_club`   (`club_id`),
    KEY `idx_mr_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
