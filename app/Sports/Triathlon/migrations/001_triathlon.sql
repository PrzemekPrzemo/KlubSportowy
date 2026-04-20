CREATE TABLE IF NOT EXISTS triathlon_athletes (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    wts_id           VARCHAR(30) DEFAULT NULL,
    usat_id          VARCHAR(30) DEFAULT NULL,
    primary_distance ENUM('super_sprint','sprint','olympic','half','full') NOT NULL DEFAULT 'olympic',
    UNIQUE KEY uq_member_club (member_id, club_id),
    INDEX (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS triathlon_results (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id           INT UNSIGNED NOT NULL,
    member_id         INT UNSIGNED NOT NULL,
    event_name        VARCHAR(200) NOT NULL,
    event_date        DATE NOT NULL,
    distance_type     ENUM('super_sprint','sprint','olympic','half','full') NOT NULL DEFAULT 'olympic',
    swim_time         INT UNSIGNED DEFAULT NULL COMMENT 'seconds',
    t1_time           INT UNSIGNED DEFAULT NULL COMMENT 'seconds',
    bike_time         INT UNSIGNED DEFAULT NULL COMMENT 'seconds',
    t2_time           INT UNSIGNED DEFAULT NULL COMMENT 'seconds',
    run_time          INT UNSIGNED DEFAULT NULL COMMENT 'seconds',
    total_time        INT UNSIGNED DEFAULT NULL COMMENT 'seconds',
    age_group         VARCHAR(30) DEFAULT NULL,
    ag_placement      SMALLINT UNSIGNED DEFAULT NULL,
    overall_placement SMALLINT UNSIGNED DEFAULT NULL,
    dnf               TINYINT(1) NOT NULL DEFAULT 0,
    dns               TINYINT(1) NOT NULL DEFAULT 0,
    notes             TEXT DEFAULT NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
