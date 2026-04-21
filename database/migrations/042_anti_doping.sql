-- M4: Anti-doping declarations + uniwersalne minor_consents
-- Anti-doping: wymóg WADA dla weightlifting (IWF), boxing (PZBoks), swimming
--              (FINA), taekwondo (WTF), cycling (UCI)
-- Minor consents: wymóg prawny dla każdego małoletniego (14-18 lat)

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS anti_doping_declarations (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id          INT UNSIGNED NOT NULL,
    member_id        INT UNSIGNED NOT NULL,
    declaration_type ENUM('WADA','POLADA','IWF','UCI','FINA','WTF','narodowa') NOT NULL DEFAULT 'WADA',
    signed_date      DATE NOT NULL,
    valid_until      DATE NOT NULL,
    document_path    VARCHAR(255) NULL,
    signed_ip        VARCHAR(45) NULL,
    witness          VARCHAR(150) NULL,
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ad_club_member` (`club_id`, `member_id`),
    KEY `idx_ad_valid`        (`valid_until`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS minor_consents (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id             INT UNSIGNED NOT NULL,
    member_id           INT UNSIGNED NOT NULL,
    guardian_name       VARCHAR(150) NOT NULL,
    guardian_id_number  VARCHAR(30) NULL COMMENT 'nr dowodu / PESEL opiekuna',
    guardian_phone      VARCHAR(30) NULL,
    guardian_email      VARCHAR(150) NULL,
    photo_consent       TINYINT(1) NOT NULL DEFAULT 0,
    media_consent       TINYINT(1) NOT NULL DEFAULT 0,
    travel_consent      TINYINT(1) NOT NULL DEFAULT 0,
    medical_decisions   TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'zgoda na podejmowanie decyzji medycznych w sytuacji awaryjnej',
    signed_date         DATE NOT NULL,
    valid_until         DATE NULL,
    document_path       VARCHAR(255) NULL,
    signed_ip           VARCHAR(45) NULL,
    notes               TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_mc_member` (`club_id`, `member_id`),
    KEY `idx_mc_club` (`club_id`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
