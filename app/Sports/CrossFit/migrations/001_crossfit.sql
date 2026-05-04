CREATE TABLE IF NOT EXISTS crossfit_wods (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    name           VARCHAR(120) NOT NULL,
    wod_type       ENUM('amrap','emom','for_time','max_reps','for_load','chipper','ladder','tabata') NOT NULL DEFAULT 'for_time',
    description    TEXT DEFAULT NULL,
    time_cap       SMALLINT UNSIGNED DEFAULT NULL COMMENT 'minutes',
    benchmark_name VARCHAR(50) DEFAULT NULL,
    created_by     INT UNSIGNED DEFAULT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crossfit_scores (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    wod_id     INT UNSIGNED NOT NULL,
    member_id  INT UNSIGNED NOT NULL,
    score      VARCHAR(30) NOT NULL,
    rx         TINYINT(1) NOT NULL DEFAULT 0,
    scaled     TINYINT(1) NOT NULL DEFAULT 0,
    notes      TEXT DEFAULT NULL,
    score_date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (wod_id),
    INDEX (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (wod_id) REFERENCES crossfit_wods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crossfit_prs (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    member_id  INT UNSIGNED NOT NULL,
    movement   VARCHAR(80) NOT NULL,
    pr_value   VARCHAR(30) NOT NULL,
    unit       ENUM('kg','lb','reps','time','m','cal') NOT NULL DEFAULT 'kg',
    pr_date    DATE NOT NULL,
    notes      TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
