-- BJJ belts
CREATE TABLE IF NOT EXISTS bjj_belts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    belt_color   ENUM('white','blue','purple','brown','black') NOT NULL DEFAULT 'white',
    stripes      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    gi           ENUM('gi','nogi','both') NOT NULL DEFAULT 'gi',
    exam_date    DATE NOT NULL,
    examiner     VARCHAR(120) DEFAULT NULL,
    notes        TEXT DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- BJJ match results
CREATE TABLE IF NOT EXISTS bjj_results (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    event_name     VARCHAR(200) NOT NULL,
    event_date     DATE NOT NULL,
    opponent       VARCHAR(120) DEFAULT NULL,
    result         ENUM('win','loss','draw','dq') NOT NULL DEFAULT 'win',
    method         ENUM('submission','points','decision','referee','walkover') DEFAULT NULL,
    weight_category VARCHAR(30) DEFAULT NULL,
    gi             ENUM('gi','nogi') NOT NULL DEFAULT 'gi',
    placement      TINYINT UNSIGNED DEFAULT NULL,
    notes          TEXT DEFAULT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
