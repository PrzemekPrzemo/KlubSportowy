-- 068: Konfigurowalny workflow onboardingu czlonkow per klub.
-- Pozwala zarzadowi klubu okreslic ktore pola sa wymagane,
-- jakie zgody nalezy zebrac, limity wieku, custom pola i auto-assigny.

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `club_onboarding_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id` INT UNSIGNED NOT NULL,
    -- Pola wymagane (standardowo opcjonalne — zarzad moze wymusic)
    `require_pesel` TINYINT(1) NOT NULL DEFAULT 0,
    `require_address` TINYINT(1) NOT NULL DEFAULT 0,
    `require_emergency_contact` TINYINT(1) NOT NULL DEFAULT 0,
    `require_medical_consent` TINYINT(1) NOT NULL DEFAULT 0,
    `require_photo` TINYINT(1) NOT NULL DEFAULT 0,
    `require_parent_data_for_minors` TINYINT(1) NOT NULL DEFAULT 1,
    -- Zgody konfigurowalne
    `custom_consents` JSON NULL COMMENT 'Array obiektow {key, label, body, required, version}',
    -- Auto-assign po dodaniu nowego czlonka
    `auto_assign_sport_id` INT UNSIGNED NULL COMMENT 'Domyslny sport (FK club_sports.id) jesli klub multi-sport',
    `auto_assign_fee_rate_id` INT UNSIGNED NULL COMMENT 'Domyslna stawka skladki (FK fee_rates.id)',
    `auto_send_welcome_email` TINYINT(1) NOT NULL DEFAULT 1,
    `welcome_email_template` VARCHAR(80) NULL COMMENT 'Klucz email_template (np. member_welcome)',
    -- Walidacja wieku
    `min_age_years` TINYINT UNSIGNED NULL COMMENT 'Minimalny wiek (NULL = brak limitu)',
    `max_age_years` TINYINT UNSIGNED NULL,
    `require_parent_consent_under_age` TINYINT UNSIGNED NOT NULL DEFAULT 18,
    -- Custom pola dodatkowe
    `custom_fields` JSON NULL COMMENT 'Array obiektow {key, label, type, required, options[]}',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Konfiguracja workflow onboardingu czlonka per klub';

-- Member custom field values
CREATE TABLE IF NOT EXISTS `member_custom_field_values` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id` INT UNSIGNED NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `field_key` VARCHAR(60) NOT NULL,
    `field_value` TEXT NULL,
    UNIQUE KEY `uniq_member_field` (`member_id`, `field_key`),
    KEY `idx_mcfv_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wartosci customowych pol onboardingu per czlonek';

-- Acceptance log dla zgod (audit + RODO)
CREATE TABLE IF NOT EXISTS `member_consent_acceptances` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id` INT UNSIGNED NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `consent_key` VARCHAR(60) NOT NULL COMMENT 'np. rodo, regulamin, marketing',
    `consent_version` VARCHAR(20) NULL,
    `accepted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `accepted_ip` VARCHAR(45) NULL,
    KEY `idx_member_consent` (`member_id`, `consent_key`),
    KEY `idx_mca_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log akceptacji zgod RODO/regulamin per czlonek';

SET foreign_key_checks = 1;
