-- Equestrian: horse inventory, rider-horse assignments, competition results

CREATE TABLE IF NOT EXISTS equestrian_horses (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    name         VARCHAR(100) NOT NULL,
    breed        VARCHAR(100),
    birth_year   YEAR,
    color        VARCHAR(60),
    sex          ENUM('stallion','mare','gelding') DEFAULT 'gelding',
    passport_no  VARCHAR(80),
    owner_name   VARCHAR(150),
    status       ENUM('active','sick','retired') NOT NULL DEFAULT 'active',
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equestrian_assignments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id     INT UNSIGNED NOT NULL,
    horse_id    INT UNSIGNED NOT NULL,
    member_id   INT UNSIGNED NOT NULL,
    start_date  DATE NOT NULL,
    end_date    DATE,
    discipline  ENUM('dressage','jumping','eventing','endurance','reining','vaulting','driving') DEFAULT 'jumping',
    notes       TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (horse_id)  REFERENCES equestrian_horses(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equestrian_results (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    horse_id         INT UNSIGNED,
    competition_name VARCHAR(200) NOT NULL,
    competition_date DATE         NOT NULL,
    discipline       ENUM('dressage','jumping','eventing','endurance','reining','vaulting','driving') DEFAULT 'jumping',
    class_level      VARCHAR(50),
    placement        TINYINT UNSIGNED,
    score            DECIMAL(6,2),
    faults           DECIMAL(5,1),
    time_seconds     DECIMAL(7,2),
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (horse_id)  REFERENCES equestrian_horses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
