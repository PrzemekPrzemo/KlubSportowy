-- Kayaking (PZKajak)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS kayak_boats (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    boat_type      ENUM('K1','C1','K2','C2','K4','C4') NOT NULL DEFAULT 'K1',
    name           VARCHAR(150) NOT NULL,
    hull_material  VARCHAR(80) NULL,
    year_built     SMALLINT UNSIGNED NULL,
    purchase_date  DATE NULL,
    state          ENUM('nowa','dobra','używana','do_serwisu','wycofana') NOT NULL DEFAULT 'dobra',
    location       VARCHAR(150) NULL,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_kb_club` (`club_id`),
    KEY `idx_kb_type` (`boat_type`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kayak_results (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    boat_id        INT UNSIGNED NULL,
    discipline     ENUM('sprint','długi_dystans','slalom','maraton','rafting','dragon_boat') NOT NULL DEFAULT 'sprint',
    event_name     VARCHAR(200) NOT NULL,
    event_date     DATE NOT NULL,
    venue          VARCHAR(200) NULL,
    category       VARCHAR(50) NULL,
    distance_m     INT UNSIGNED NOT NULL,
    time_ms        INT UNSIGNED NULL,
    place          SMALLINT UNSIGNED NULL,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_kr_club`   (`club_id`),
    KEY `idx_kr_member` (`member_id`),
    KEY `idx_kr_boat`   (`boat_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)      ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id)    ON DELETE CASCADE,
    FOREIGN KEY (boat_id)   REFERENCES kayak_boats(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
