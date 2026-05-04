CREATE TABLE IF NOT EXISTS table_tennis_results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    competition_name VARCHAR(200) NOT NULL,
    competition_date DATE         NOT NULL,
    age_category     VARCHAR(50),
    category         VARCHAR(50),
    placement        TINYINT UNSIGNED,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
