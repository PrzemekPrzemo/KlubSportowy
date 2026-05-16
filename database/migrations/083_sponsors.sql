-- 083: Sponsorship management — per-club sponsor catalog with exposure tracking.
--
-- Trzy tabele:
--   * sponsors            — katalog sponsorów per-klub (CRUD).
--   * sponsor_exposures   — log ekspozycji (portal_view / email_view / landing_view)
--                           dla reporting "ile razy logo zostało wyświetlone".
--   * sponsor_alert_log   — idempotency dla cli/sponsors_expiry_alerts.php (nie spamuje
--                           tym samym alertem przy każdym uruchomieniu crona).
--
-- Multi-tenant: sponsors.club_id FK → clubs.id ON DELETE CASCADE.
-- sponsor_exposures.sponsor_id FK → sponsors.id ON DELETE CASCADE
-- (więc usunięcie klubu kaskaduje przez sponsorów do ekspozycji).

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `sponsors` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(200) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    website VARCHAR(255) NULL,
    logo_path VARCHAR(500) NULL,
    tier ENUM('platinum','gold','silver','bronze','partner') NOT NULL DEFAULT 'partner',
    contract_value DECIMAL(10,2) NULL,
    contract_start DATE NULL,
    contract_end DATE NULL,
    notes TEXT NULL,
    display_in_portal TINYINT(1) NOT NULL DEFAULT 1,
    display_in_emails TINYINT(1) NOT NULL DEFAULT 1,
    display_weight INT NOT NULL DEFAULT 100,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_club_active (club_id, active),
    KEY idx_club_contract_end (club_id, contract_end),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sponsorzy per-klub (logo, kontrakt, display flags)';

CREATE TABLE IF NOT EXISTS `sponsor_exposures` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sponsor_id INT UNSIGNED NOT NULL,
    context ENUM('portal_view','email_view','landing_view') NOT NULL,
    member_id INT UNSIGNED NULL,
    shown_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sponsor_date (sponsor_id, shown_at),
    KEY idx_context_date (context, shown_at),
    FOREIGN KEY (sponsor_id) REFERENCES sponsors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log ekspozycji sponsorów (portal/email/landing)';

CREATE TABLE IF NOT EXISTS `sponsor_alert_log` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sponsor_id INT UNSIGNED NOT NULL,
    alert_type VARCHAR(40) NOT NULL COMMENT 'expiring_30d / expiring_14d / expiring_7d',
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_sponsor_alert (sponsor_id, alert_type),
    FOREIGN KEY (sponsor_id) REFERENCES sponsors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Idempotency log dla expiry alerts (1 alert na sponsor+typ)';

-- Seed: event w katalogu emailowym
INSERT IGNORE INTO `email_event_catalog`
    (`code`, `name`, `description`, `category`, `default_subject`, `default_body`, `available_variables`, `sort_order`)
VALUES
('sponsor_expiring', 'Wygasajacy kontrakt sponsorski',
 'Alert dla zarzadu klubu gdy kontrakt sponsorski zblizyly sie do konca (30/14/7 dni)',
 'compliance',
 'Kontrakt sponsorski {{sponsor.name}} wygasa za {{sponsor.days_left}} dni',
 'Zarzad klubu {{club.name}},\n\nKontrakt sponsorski z {{sponsor.name}} (tier: {{sponsor.tier}}) wygasa {{sponsor.contract_end}} (za {{sponsor.days_left}} dni).\n\nWartosc kontraktu: {{sponsor.contract_value}}\nOsoba kontaktowa: {{sponsor.contact_person}} ({{sponsor.email}}, {{sponsor.phone}}).\n\nZalecamy rozpoczecie procesu odnowienia.\n\n--\nClubDesk',
 '["sponsor.name","sponsor.tier","sponsor.contract_end","sponsor.contract_value","sponsor.days_left","sponsor.contact_person","sponsor.email","sponsor.phone","club.name"]',
 200);

SET foreign_key_checks = 1;
