-- M6: Uprawnienia trenerskie per sport (wymóg PZS + ustawa o sporcie art. 41)
-- Klasy trenerskie PZS: instruktor → trener klasy II → klasy I → klasy mistrzowskiej
-- Sędziowskie: klasa III → II → I → sędzia państwowy

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS coach_certifications (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NULL  COMMENT 'trener=zawodnik klubu',
    user_id        INT UNSIGNED NULL  COMMENT 'lub pracownik (user)',
    sport_key      VARCHAR(50) NOT NULL,
    cert_name      VARCHAR(200) NOT NULL,
    cert_level     ENUM(
        'instruktor_sportu',
        'instruktor_rekreacji',
        'trener_klasy_II',
        'trener_klasy_I',
        'trener_klasy_mistrzowskiej',
        'sedzia_III',
        'sedzia_II',
        'sedzia_I',
        'sedzia_panstwowy',
        'ratownik_wodny',
        'pierwsza_pomoc',
        'inne'
    ) NOT NULL DEFAULT 'instruktor_sportu',
    cert_number    VARCHAR(80) NULL,
    issuing_body   VARCHAR(200) NULL COMMENT 'np. PZKosz, PZP, AWF',
    issued_at      DATE NOT NULL,
    valid_until    DATE NULL COMMENT 'NULL = bezterminowy',
    document_path  VARCHAR(255) NULL,
    notes          TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_cc_club_sport` (`club_id`, `sport_key`),
    KEY `idx_cc_member`      (`member_id`),
    KEY `idx_cc_user`        (`user_id`),
    KEY `idx_cc_valid`       (`valid_until`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
