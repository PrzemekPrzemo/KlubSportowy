-- Archery: bow inventory and competition score cards

CREATE TABLE IF NOT EXISTS archery_bows (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED,
    bow_type     ENUM('recurve','compound','barebow','longbow','traditional') NOT NULL DEFAULT 'recurve',
    brand        VARCHAR(100),
    model        VARCHAR(100),
    draw_weight  DECIMAL(5,1),
    draw_length  DECIMAL(5,1),
    limb_length  VARCHAR(20),
    serial_no    VARCHAR(80),
    owned_by     ENUM('club','member') NOT NULL DEFAULT 'club',
    status       ENUM('active','maintenance','retired') NOT NULL DEFAULT 'active',
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS archery_scores (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    competition_name VARCHAR(200),
    score_date       DATE         NOT NULL,
    discipline       ENUM('18m','25m','50m','70m','90m','outdoor_50','outdoor_70','field','3D','recurve_open','compound_open') NOT NULL,
    rounds           TINYINT UNSIGNED DEFAULT 1,
    total_score      SMALLINT UNSIGNED NOT NULL,
    tens             TINYINT UNSIGNED,
    xs               TINYINT UNSIGNED,
    bow_type         ENUM('recurve','compound','barebow','longbow','traditional'),
    age_category     VARCHAR(50),
    placement        TINYINT UNSIGNED,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
