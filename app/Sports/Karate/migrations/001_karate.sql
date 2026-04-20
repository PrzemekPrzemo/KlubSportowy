-- Karate: belt gradings (kyu/dan) and competition results

CREATE TABLE IF NOT EXISTS karate_belts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    belt_level   ENUM('10kyu','9kyu','8kyu','7kyu','6kyu','5kyu','4kyu','3kyu','2kyu','1kyu',
                      '1dan','2dan','3dan','4dan','5dan','6dan','7dan','8dan','9dan','10dan') NOT NULL,
    granted_date DATE         NOT NULL,
    examiner     VARCHAR(120),
    location     VARCHAR(150),
    style        ENUM('shotokan','wado_ryu','goju_ryu','shito_ryu','kyokushin','other') DEFAULT 'shotokan',
    notes        TEXT,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS karate_results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    competition_name VARCHAR(200) NOT NULL,
    competition_date DATE         NOT NULL,
    age_category     VARCHAR(50),
    weight_class     ENUM('-50','-55','-60','-67','-75','-84','+84','open'),
    category         ENUM('kumite','kata','team_kumite','team_kata') NOT NULL DEFAULT 'kumite',
    placement        TINYINT UNSIGNED,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
