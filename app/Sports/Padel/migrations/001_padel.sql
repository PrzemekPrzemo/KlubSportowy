CREATE TABLE IF NOT EXISTS padel_courts (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(80) NOT NULL,
    surface    ENUM('sztuczna','naturalna','beton','indoor') NOT NULL DEFAULT 'sztuczna',
    indoor     TINYINT(1) NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS padel_pairs (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id         INT UNSIGNED NOT NULL,
    player1_id      INT UNSIGNED NOT NULL,
    player2_id      INT UNSIGNED NOT NULL,
    pair_name       VARCHAR(100) DEFAULT NULL,
    category        ENUM('men','women','mixed') NOT NULL DEFAULT 'mixed',
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    ranking_points  INT NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (player1_id),
    INDEX (player2_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS padel_reservations (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    court_id       INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime   DATETIME NOT NULL,
    status         ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    paid           TINYINT(1) NOT NULL DEFAULT 0,
    notes          TEXT DEFAULT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (court_id),
    INDEX (member_id),
    INDEX (start_datetime),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (court_id) REFERENCES padel_courts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
