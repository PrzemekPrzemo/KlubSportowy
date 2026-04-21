-- M5: Ujednolicony sprzęt klubowy (dla wszystkich sportów)
-- Dla: hokej (kije/łyżwy), tenis (rakiety), boks (rękawice), szermierka (bronie),
--      wspinaczka (liny/karabinki), taekwondo (dochogi), weightlifting (sztangi),
--      cycling (rowery klubowe), pływanie (akcesoria)

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS club_equipment_items (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id         INT UNSIGNED NOT NULL,
    sport_key       VARCHAR(50) NULL COMMENT 'klucz sportu lub NULL dla ogólnego',
    category        VARCHAR(80) NOT NULL COMMENT 'rakieta/kij/rower/linka/rękawice/...',
    name            VARCHAR(200) NOT NULL,
    serial_number   VARCHAR(80) NULL,
    brand           VARCHAR(120) NULL,
    model           VARCHAR(120) NULL,
    size            VARCHAR(40) NULL,
    purchase_date   DATE NULL,
    purchase_price  DECIMAL(10,2) NULL,
    state           ENUM('nowy','dobry','używany','do_serwisu','wycofany') NOT NULL DEFAULT 'dobry',
    location        VARCHAR(150) NULL COMMENT 'magazyn, szafka, hala',
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_cei_club`     (`club_id`),
    KEY `idx_cei_sport`    (`sport_key`),
    KEY `idx_cei_category` (`category`),
    KEY `idx_cei_state`    (`state`),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS club_equipment_assignments (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id       INT UNSIGNED NOT NULL,
    item_id       INT UNSIGNED NOT NULL,
    member_id     INT UNSIGNED NOT NULL,
    issued_at     DATETIME NOT NULL,
    returned_at   DATETIME NULL,
    issued_by     INT UNSIGNED NULL COMMENT 'user_id wydającego',
    returned_by   INT UNSIGNED NULL,
    issue_notes   TEXT NULL,
    return_notes  TEXT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_cea_club`   (`club_id`),
    KEY `idx_cea_item`   (`item_id`),
    KEY `idx_cea_member` (`member_id`),
    KEY `idx_cea_active` (`returned_at`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)                ON DELETE CASCADE,
    FOREIGN KEY (item_id)   REFERENCES club_equipment_items(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id)              ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
