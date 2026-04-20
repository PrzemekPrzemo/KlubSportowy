-- Dance Sport: couples (partnerships) and competition results

CREATE TABLE IF NOT EXISTS dance_couples (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    leader_id      INT UNSIGNED NOT NULL,
    follower_id    INT UNSIGNED,
    couple_name    VARCHAR(150),
    class_level    ENUM('D','C','B','A','S','M') NOT NULL DEFAULT 'D',
    discipline     ENUM('standard','latin','ten_dances','formation_standard','formation_latin') NOT NULL DEFAULT 'standard',
    active_from    DATE,
    active_to      DATE,
    status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)     REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (leader_id)   REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (follower_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dance_results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    couple_id        INT UNSIGNED,
    leader_id        INT UNSIGNED NOT NULL,
    competition_name VARCHAR(200) NOT NULL,
    competition_date DATE         NOT NULL,
    discipline       ENUM('standard','latin','ten_dances','formation_standard','formation_latin') NOT NULL DEFAULT 'standard',
    class_level      ENUM('D','C','B','A','S','M') NOT NULL DEFAULT 'D',
    age_category     VARCHAR(50),
    placement        TINYINT UNSIGNED,
    round_reached    VARCHAR(30),
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)    REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (couple_id)  REFERENCES dance_couples(id) ON DELETE SET NULL,
    FOREIGN KEY (leader_id)  REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
