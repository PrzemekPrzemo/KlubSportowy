-- Migration 099: SMS templates multilang (PL/EN)
--
-- Cel:
--   - sms_template_catalog            — global katalog kodow + default body (PL)
--   - sms_template_translations       — wpisy per-locale (body)
--
-- Architektura analogiczna do email_event_catalog_translations z migr. 098.
-- W przyszlosci SmsService::sendFromTemplate(...) bedzie respektowal
-- member.preferred_locale (cascade jak w EmailService).
--
-- Whitelist locale: tylko 'pl' i 'en'.

SET foreign_key_checks = 0;

-- ============================================================
-- sms_template_catalog
-- ============================================================
CREATE TABLE IF NOT EXISTS `sms_template_catalog` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`         VARCHAR(50)  NOT NULL,
    `description`  VARCHAR(500) NULL,
    `category`     VARCHAR(50)  NULL,
    `default_body` VARCHAR(500) NOT NULL,
    `sort_order`   INT          NOT NULL DEFAULT 0,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_sms_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Katalog szablonow SMS (kody systemowe + default body w PL)';

-- ============================================================
-- sms_template_translations
-- ============================================================
CREATE TABLE IF NOT EXISTS `sms_template_translations` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `template_id` INT UNSIGNED NOT NULL,
    `locale`      CHAR(2)      NOT NULL,
    `body`        VARCHAR(500) NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_sms_tmpl_locale` (`template_id`, `locale`),
    KEY `idx_smst_locale` (`locale`),
    FOREIGN KEY (`template_id`) REFERENCES `sms_template_catalog`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tlumaczenia body szablonow SMS per locale';

-- ============================================================
-- Seed: TOP 5 SMS templates (PL defaults)
-- ============================================================
INSERT IGNORE INTO `sms_template_catalog` (`code`, `description`, `category`, `default_body`, `sort_order`) VALUES
  ('payment_reminder',  'SMS przypomnienie o platnosci',  'payments',  'Skladka {amount} PLN do zaplaty do {due_date}. Link: {url}', 10),
  ('training_cancelled','SMS anulowany trening',          'trainings', 'Trening {date} odwolany. Szczegoly: {url}',                     20),
  ('event_reminder',    'SMS przypomnienie wydarzenie',   'events',    '{event_name} dzis o {time}. Lokalizacja: {location}',           30),
  ('mfa_code',          'SMS kod 2FA',                    'auth',      'Kod ClubDesk: {code}. Wazny 5 minut.',                          40),
  ('emergency_alert',   'SMS alarm awaryjny',             'security',  '{club_name}: {message}',                                        50);

-- PL translations from default_body
INSERT IGNORE INTO `sms_template_translations` (`template_id`, `locale`, `body`)
SELECT `id`, 'pl', `default_body` FROM `sms_template_catalog`;

-- EN translations
INSERT IGNORE INTO `sms_template_translations` (`template_id`, `locale`, `body`)
SELECT `id`, 'en', 'Membership fee {amount} PLN due by {due_date}. Link: {url}'
FROM `sms_template_catalog` WHERE `code` = 'payment_reminder';

INSERT IGNORE INTO `sms_template_translations` (`template_id`, `locale`, `body`)
SELECT `id`, 'en', 'Training on {date} cancelled. Details: {url}'
FROM `sms_template_catalog` WHERE `code` = 'training_cancelled';

INSERT IGNORE INTO `sms_template_translations` (`template_id`, `locale`, `body`)
SELECT `id`, 'en', '{event_name} today at {time}. Location: {location}'
FROM `sms_template_catalog` WHERE `code` = 'event_reminder';

INSERT IGNORE INTO `sms_template_translations` (`template_id`, `locale`, `body`)
SELECT `id`, 'en', 'ClubDesk code: {code}. Valid for 5 minutes.'
FROM `sms_template_catalog` WHERE `code` = 'mfa_code';

INSERT IGNORE INTO `sms_template_translations` (`template_id`, `locale`, `body`)
SELECT `id`, 'en', '{club_name}: {message}'
FROM `sms_template_catalog` WHERE `code` = 'emergency_alert';

SET foreign_key_checks = 1;
