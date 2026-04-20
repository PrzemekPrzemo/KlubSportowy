CREATE TABLE IF NOT EXISTS sailing_boats (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id             INT UNSIGNED NOT NULL,
    name                VARCHAR(120) NOT NULL,
    registration_number VARCHAR(60) DEFAULT NULL,
    class               VARCHAR(60) DEFAULT NULL,
    length_m            DECIMAL(5,2) DEFAULT NULL,
    year_built          SMALLINT UNSIGNED DEFAULT NULL,
    hull_material       VARCHAR(50) DEFAULT NULL,
    insurance_expiry    DATE DEFAULT NULL,
    next_inspection     DATE DEFAULT NULL,
    owner_member_id     INT UNSIGNED DEFAULT NULL,
    orc_cert            JSON DEFAULT NULL,
    notes               TEXT DEFAULT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sailing_crew (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    boat_id    INT UNSIGNED NOT NULL,
    member_id  INT UNSIGNED NOT NULL,
    role       ENUM('skipper','crew','navigator','tactician','trimmer') NOT NULL DEFAULT 'crew',
    is_permanent TINYINT(1) NOT NULL DEFAULT 0,
    joined_at  DATE DEFAULT NULL,
    UNIQUE KEY uq_boat_member (boat_id, member_id),
    INDEX (club_id),
    INDEX (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (boat_id) REFERENCES sailing_boats(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sailing_races (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(200) NOT NULL,
    race_date  DATE NOT NULL,
    race_type  ENUM('regata','rejs','zawody','trening') NOT NULL DEFAULT 'regata',
    distance_nm DECIMAL(6,1) DEFAULT NULL,
    location   VARCHAR(150) DEFAULT NULL,
    results    JSON DEFAULT NULL,
    notes      TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (race_date),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sailing_licenses (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    license_type ENUM('OM','ŻJ','SJ','KJ','KM_ż','KM_m') NOT NULL DEFAULT 'ŻJ',
    license_number VARCHAR(60) DEFAULT NULL,
    issue_date   DATE DEFAULT NULL,
    valid_until  DATE DEFAULT NULL,
    issued_by    VARCHAR(120) DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
