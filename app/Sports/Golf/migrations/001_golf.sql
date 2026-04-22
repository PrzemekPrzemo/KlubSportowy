-- Golf (PZGolfa)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS golf_handicaps (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    whs_index    DECIMAL(4,1) NOT NULL,
    updated_at   DATE NOT NULL,
    source       ENUM('pzga','klubowy','wsh_official','manual') NOT NULL DEFAULT 'klubowy',
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_gh_club`   (`club_id`),
    KEY `idx_gh_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS golf_rounds (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    course_name    VARCHAR(200) NOT NULL,
    round_date     DATE NOT NULL,
    tees           ENUM('white','yellow','blue','red','black','green') NOT NULL DEFAULT 'yellow',
    holes          TINYINT UNSIGNED NOT NULL DEFAULT 18,
    total_strokes  SMALLINT UNSIGNED NULL,
    gross_score    SMALLINT NULL COMMENT 'relative to par',
    net_score      DECIMAL(5,1) NULL,
    slope_rating   SMALLINT UNSIGNED NULL COMMENT '55-155',
    course_rating  DECIMAL(4,1) NULL,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_gr_club`   (`club_id`),
    KEY `idx_gr_member` (`member_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
