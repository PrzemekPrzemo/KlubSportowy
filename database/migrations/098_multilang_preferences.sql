-- Migration 098: Multilang preferences (PL/EN)
--
-- Cel:
--   - members.preferred_locale — preferowany jezyk czlonka (portal, emaile, PDF)
--   - clubs.default_locale     — default klubu (dla anonimowych / nowo dodanych)
--   - email_event_catalog_translations — wpisy per-locale (subject + body)
--
-- Cascade resolve: member.preferred_locale -> session -> club.default_locale
--                  -> Accept-Language -> hard 'pl'
--
-- Whitelist locale: tylko 'pl' i 'en' (defense-in-depth na poziomie kontrolera).

SET foreign_key_checks = 0;

-- ============================================================
-- members.preferred_locale
-- ============================================================
ALTER TABLE `members`
    ADD COLUMN IF NOT EXISTS `preferred_locale` CHAR(2) NULL
        COMMENT 'pl|en — preferowany jezyk portalu/emaili/PDF (NULL = dziedzicz z klubu)'
        AFTER `email`;

-- ============================================================
-- clubs.default_locale
-- ============================================================
ALTER TABLE `clubs`
    ADD COLUMN IF NOT EXISTS `default_locale` CHAR(2) NOT NULL DEFAULT 'pl'
        COMMENT 'pl|en — fallback locale dla nowych czlonkow klubu i anonimowej komunikacji';

-- ============================================================
-- email_event_catalog_translations
-- ============================================================
CREATE TABLE IF NOT EXISTS `email_event_catalog_translations` (
    `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id`  INT UNSIGNED NOT NULL,
    `locale`    CHAR(2)      NOT NULL,
    `subject`   VARCHAR(500) NOT NULL,
    `body`      TEXT         NOT NULL,
    `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_event_locale` (`event_id`, `locale`),
    KEY `idx_eect_locale` (`locale`),
    FOREIGN KEY (`event_id`) REFERENCES `email_event_catalog`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tlumaczenia subject+body szablonow email per locale';

-- Migracja: skopiuj istniejace default_subject + default_body jako wpisy 'pl'.
INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT `id`, 'pl', COALESCE(`default_subject`, ''), COALESCE(`default_body`, '')
FROM `email_event_catalog`
WHERE `default_subject` IS NOT NULL OR `default_body` IS NOT NULL;

-- ============================================================
-- EN translations dla TOP 10 najwazniejszych eventow
-- (Best-effort INSERT IGNORE — pomija jesli event code nie istnieje).
-- ============================================================
INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'Welcome to {{club.name}}!',
    'Hi {{member.first_name}},\n\nWelcome to {{club.name}}! Your membership number is {{member.member_number}}.\n\nBest regards,\nThe {{club.name}} team'
FROM `email_event_catalog` WHERE `code` = 'member_welcome';

INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'Password reset — {{club.name}}',
    'Hi {{member.first_name}},\n\nClick the link below to reset your password:\n{{reset_link}}\n\nIf you did not request this, please ignore this email.'
FROM `email_event_catalog` WHERE `code` = 'member_password_reset';

INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'Membership fee due: {{fee.due_date}}',
    'Hi {{member.first_name}},\n\nThis is a reminder that your membership fee of {{fee.amount}} PLN is due on {{fee.due_date}}.\n\nPay online: {{payment_link}}\n\nThanks,\n{{club.name}}'
FROM `email_event_catalog` WHERE `code` = 'fee_reminder';

INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'Overdue membership fee — {{club.name}}',
    'Hi {{member.first_name}},\n\nYour membership fee of {{fee.amount}} PLN was due on {{fee.due_date}} and is now overdue.\n\nPlease settle it as soon as possible: {{payment_link}}\n\nThanks,\n{{club.name}}'
FROM `email_event_catalog` WHERE `code` = 'fee_overdue';

INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'Payment received — {{club.name}}',
    'Hi {{member.first_name}},\n\nWe have received your payment of {{payment.amount}} PLN. Thank you!\n\n{{club.name}}'
FROM `email_event_catalog` WHERE `code` IN ('payment_received','fee_paid');

INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'Medical exam expiring — {{club.name}}',
    'Hi {{member.first_name}},\n\nYour medical exam expires on {{medical.expiry_date}}. Please schedule a renewal before that date.\n\n{{club.name}}'
FROM `email_event_catalog` WHERE `code` IN ('medical_expiring','medical_exam_expiring');

INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'GDPR request received — {{club.name}}',
    'Hi {{member.first_name}},\n\nWe have received your GDPR request ({{gdpr.request_type}}). We will process it within 30 days as required by law.\n\n{{club.name}}'
FROM `email_event_catalog` WHERE `code` IN ('gdpr_request_confirmation','gdpr_request_received');

INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'Your data export is ready — {{club.name}}',
    'Hi {{member.first_name}},\n\nYour personal data export is ready for download:\n{{gdpr.download_link}}\n\nThe link expires in 7 days.\n\n{{club.name}}'
FROM `email_event_catalog` WHERE `code` IN ('gdpr_export_ready','gdpr_export');

INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'Data deletion confirmed — {{club.name}}',
    'Hi {{member.first_name}},\n\nWe confirm that your personal data has been deleted from our systems in accordance with your GDPR request.\n\n{{club.name}}'
FROM `email_event_catalog` WHERE `code` IN ('gdpr_delete_confirmed','gdpr_delete');

INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'Tournament protocol available — {{tournament.name}}',
    'Hi {{member.first_name}},\n\nThe final protocol for the tournament "{{tournament.name}}" is now available.\n\n{{club.name}}'
FROM `email_event_catalog` WHERE `code` IN ('tournament_finished_protocol','tournament_finished');

INSERT IGNORE INTO `email_event_catalog_translations` (`event_id`, `locale`, `subject`, `body`)
SELECT id, 'en',
    'Sponsor agreement expiring — {{club.name}}',
    'Sponsor {{sponsor.name}} agreement expires on {{sponsor.expiry_date}}. Please renew before that date.\n\n{{club.name}}'
FROM `email_event_catalog` WHERE `code` IN ('sponsor_expiring','sponsor_expiry');

SET foreign_key_checks = 1;
