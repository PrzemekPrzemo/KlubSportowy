-- M2: Kontakty w razie wypadku — wymóg dla sportów kontaktowych
-- Dla: boks, BJJ, hokej, taekwondo, piłka ręczna, szermierka, wspinaczka

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS member_emergency_contacts (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id       INT UNSIGNED NOT NULL,
    member_id     INT UNSIGNED NOT NULL,
    contact_name  VARCHAR(150) NOT NULL,
    relationship  ENUM('rodzic','małżonek','rodzeństwo','opiekun','partner','przyjaciel','inny') NOT NULL DEFAULT 'rodzic',
    phone         VARCHAR(30) NOT NULL,
    phone_alt     VARCHAR(30) NULL,
    email         VARCHAR(150) NULL,
    is_primary    TINYINT(1) NOT NULL DEFAULT 0,
    notes         TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_ec_club_member` (`club_id`, `member_id`),
    KEY `idx_ec_primary`     (`is_primary`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
