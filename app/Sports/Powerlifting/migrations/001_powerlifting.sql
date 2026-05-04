-- Powerlifting module migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS powerlifting_results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    competition_name VARCHAR(200) NOT NULL,
    competition_date DATE         NOT NULL,
    weight_class     VARCHAR(15)  NOT NULL,
    body_weight      DECIMAL(5,2),
    squat_best       DECIMAL(5,1),
    bench_best       DECIMAL(5,1),
    deadlift_best    DECIMAL(5,1),
    total            DECIMAL(6,1),
    wilks_coeff      DECIMAL(6,4),
    ipf_gl_points    DECIMAL(6,3),
    federation_type  ENUM('IPF','WRPF','WPC','PZTSS','raw','equipped') NOT NULL DEFAULT 'PZTSS',
    age_category     VARCHAR(50),
    placement        TINYINT UNSIGNED,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_pl_club`   (`club_id`),
    KEY `idx_pl_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS powerlifting_records (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    lift_type    ENUM('squat','bench','deadlift','total') NOT NULL,
    weight_class VARCHAR(15),
    weight_kg    DECIMAL(5,1) NOT NULL,
    set_date     DATE NOT NULL,
    competition  VARCHAR(200),
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_plr_club`   (`club_id`),
    KEY `idx_plr_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
