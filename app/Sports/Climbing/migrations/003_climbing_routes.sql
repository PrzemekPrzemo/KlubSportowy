-- Climbing: tabela dróg (routes/problems) na ścianie klubowej
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS climbing_routes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id       INT UNSIGNED NOT NULL,
    name          VARCHAR(150) NOT NULL,
    type          ENUM('prowadzenie','buldering','top-rope') NOT NULL DEFAULT 'prowadzenie',
    grade_french  VARCHAR(10) NULL COMMENT 'np. 6a, 7c+, 8a',
    grade_v       VARCHAR(10) NULL COMMENT 'np. V3, V8 (bouldering)',
    wall_name     VARCHAR(100) NULL,
    color         VARCHAR(20) NULL,
    set_by        VARCHAR(120) NULL,
    set_date      DATE NULL,
    retired       TINYINT(1) NOT NULL DEFAULT 0,
    notes         TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_croute_club` (`club_id`),
    KEY `idx_croute_type` (`type`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS climbing_sends (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id    INT UNSIGNED NOT NULL,
    member_id  INT UNSIGNED NOT NULL,
    route_id   INT UNSIGNED NOT NULL,
    send_type  ENUM('onsight','flash','redpoint','top','zone') DEFAULT 'redpoint',
    attempts   SMALLINT UNSIGNED DEFAULT 1,
    send_date  DATE NOT NULL,
    notes      TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_csend_club`   (`club_id`),
    KEY `idx_csend_member` (`member_id`),
    KEY `idx_csend_route`  (`route_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id)  REFERENCES climbing_routes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
