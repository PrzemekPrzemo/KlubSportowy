-- Wrestling module migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS wrestling_results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    competition_name VARCHAR(200) NOT NULL,
    competition_date DATE         NOT NULL,
    style            ENUM('freestyle','greco_roman','women') NOT NULL DEFAULT 'freestyle',
    weight_class     VARCHAR(15),
    age_category     VARCHAR(50),
    placement        TINYINT UNSIGNED,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_wr_club`   (`club_id`),
    KEY `idx_wr_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
