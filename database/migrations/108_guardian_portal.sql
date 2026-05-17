-- 108: Portal opiekuna (rodzica) — pelna implementacja RODO art. 8
--
-- Wymog prawny: dla wszystkich czlonkow <16 lat (RODO art. 8) klub musi
-- zbierac zgody od opiekuna prawnego, ktory musi miec mozliwosc
-- weryfikacji, zarzadzania i odwolania tych zgod.
--
-- Tabele:
--   guardians                — konta opiekunow (osobne od members)
--   guardian_members         — link M:N opiekun <-> dziecko (member)
--   guardian_minor_consents  — granularne zgody RODO art. 8 per dziecko
--
-- Multi-tenant: club_id w kazdej tabeli (defense-in-depth).

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `guardians` (
    `id`                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`                     INT UNSIGNED NOT NULL,
    `email`                       VARCHAR(255) NOT NULL,
    `phone`                       VARCHAR(50)  NULL,
    `first_name`                  VARCHAR(200) NULL,
    `last_name`                   VARCHAR(200) NULL,
    `portal_password`             CHAR(60)     NULL COMMENT 'bcrypt — null gdy konto nie aktywowane',
    `email_verified_at`           DATETIME     NULL,
    `activation_token`            CHAR(64)     NULL,
    `activation_token_expires_at` DATETIME     NULL,
    `preferred_locale`            CHAR(2)      NULL,
    `consent_terms_accepted_at`   DATETIME     NULL,
    `last_login_at`               DATETIME     NULL,
    `active`                      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`                  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_club_email` (`club_id`, `email`),
    KEY `idx_activation` (`activation_token`),
    KEY `idx_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Konta opiekunow (rodzicow) — RODO art. 8 portal';

CREATE TABLE IF NOT EXISTS `guardian_members` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `guardian_id`         INT UNSIGNED NOT NULL,
    `member_id`           INT UNSIGNED NOT NULL,
    `club_id`             INT UNSIGNED NOT NULL,
    `relationship`        ENUM('parent','legal_guardian','grandparent','other') NOT NULL DEFAULT 'parent',
    `primary_guardian`    TINYINT(1) NOT NULL DEFAULT 0,
    `can_pay`             TINYINT(1) NOT NULL DEFAULT 1,
    `can_consent`         TINYINT(1) NOT NULL DEFAULT 1,
    `invited_by_user_id`  INT UNSIGNED NULL,
    `invited_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `accepted_at`         DATETIME     NULL,
    UNIQUE KEY `uniq_guardian_member` (`guardian_id`, `member_id`),
    KEY `idx_member` (`member_id`),
    KEY `idx_club`   (`club_id`),
    FOREIGN KEY (`guardian_id`) REFERENCES `guardians`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='M:N link opiekun <-> czlonek';

CREATE TABLE IF NOT EXISTS `guardian_minor_consents` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `guardian_id`  INT UNSIGNED NOT NULL,
    `member_id`    INT UNSIGNED NOT NULL,
    `club_id`      INT UNSIGNED NOT NULL,
    `consent_type` ENUM(
        'data_processing',
        'image_use',
        'training_participation',
        'tournament_participation',
        'medical_treatment',
        'communication_email',
        'communication_sms'
    ) NOT NULL,
    `granted`      TINYINT(1) NOT NULL DEFAULT 1,
    `granted_at`   DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revoked_at`   DATETIME   NULL,
    `ip_address`   VARCHAR(45)  NULL,
    `user_agent`   VARCHAR(500) NULL,
    `notes`        TEXT NULL,
    UNIQUE KEY `uniq_guardian_member_type` (`guardian_id`, `member_id`, `consent_type`),
    KEY `idx_member_type` (`member_id`, `consent_type`),
    KEY `idx_club` (`club_id`),
    FOREIGN KEY (`guardian_id`) REFERENCES `guardians`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zgody RODO art. 8 — granularne per opiekun-dziecko-typ';

SET foreign_key_checks = 1;

-- Seed: email template dla zaproszenia opiekuna
INSERT IGNORE INTO `email_event_catalog`
    (`code`, `name`, `description`, `category`, `default_subject`, `default_body`, `available_variables`, `sort_order`)
VALUES
('guardian_invitation', 'Zaproszenie opiekuna',
 'Klub zaprasza opiekuna do portalu rodzica',
 'guardian',
 'Zaproszenie do portalu opiekuna - {{club.name}}',
 'Czesc {{guardian.first_name}},\n\nKlub {{club.name}} zaprasza Cie do portalu opiekuna dla {{member.first_name}} {{member.last_name}}.\n\nAktywuj konto: {{activation_link}}\n\nLink wygasa za 7 dni.\n\nPozdrawiamy,\nKlub {{club.name}}',
 '["guardian.first_name","member.first_name","member.last_name","club.name","activation_link"]',
 300),
('guardian_password_reset', 'Reset hasla opiekuna',
 'Link resetu hasla dla opiekuna',
 'guardian',
 'Reset hasla portalu opiekuna - {{club.name}}',
 'Czesc {{guardian.first_name}},\n\nKliknij link aby zresetowac haslo: {{reset_link}}\n\nLink wygasa za 1 godzine.\n\nPozdrawiamy,\nKlub {{club.name}}',
 '["guardian.first_name","reset_link","club.name"]',
 310);
