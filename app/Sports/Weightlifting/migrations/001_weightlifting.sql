-- Weightlifting module migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS weightlifting_results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    competition_name VARCHAR(200) NOT NULL,
    competition_date DATE         NOT NULL,
    weight_class     VARCHAR(15)  NOT NULL,
    body_weight      DECIMAL(5,2),
    snatch_best      DECIMAL(5,1),
    cleanjerk_best   DECIMAL(5,1),
    total            DECIMAL(6,1),
    sinclair_coeff   DECIMAL(6,4),
    age_category     VARCHAR(50),
    placement        TINYINT UNSIGNED,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_wl_club`   (`club_id`),
    KEY `idx_wl_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
