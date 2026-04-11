-- Migration: email queue + templates (Phase 4.1)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`       INT UNSIGNED NULL COMMENT 'NULL = globalny szablon domyślny',
  `template_type` VARCHAR(80) NOT NULL,
  `name`          VARCHAR(120) NOT NULL,
  `subject`       VARCHAR(255) NOT NULL,
  `body`          TEXT NOT NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_email_tpl` (`club_id`, `template_type`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Szablony e-mail — globalne domyślne lub nadpisane per-klub';

CREATE TABLE IF NOT EXISTS `email_queue` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `to_email`    VARCHAR(120) NOT NULL,
  `to_name`     VARCHAR(120) NULL,
  `subject`     VARCHAR(255) NOT NULL,
  `body`        TEXT NOT NULL,
  `template_type` VARCHAR(80) NULL,
  `status`      ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `error`       TEXT NULL,
  `scheduled_at` DATETIME NULL,
  `sent_at`     DATETIME NULL,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_eq_status` (`status`, `scheduled_at`),
  KEY `idx_eq_club`   (`club_id`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kolejka wiadomości e-mail do wysłania';

-- Domyślne szablony globalne (club_id NULL)
INSERT IGNORE INTO email_templates (club_id, template_type, name, subject, body) VALUES
  (NULL, 'welcome',         'Powitanie nowego zawodnika',
   'Witaj w klubie {club_name}!',
   'Cześć {first_name},\n\nWitaj w klubie {club_name}! Twój numer członkowski to {member_number}.\n\nPozdrawiamy,\nZarząd klubu {club_name}'),

  (NULL, 'fee_reminder',    'Przypomnienie o składce',
   'Przypomnienie o składce — {club_name}',
   'Cześć {first_name},\n\nPrzypominamy o zaległej składce w klubie {club_name}.\nKwota: {amount} zł.\n\nProsimy o uregulowanie w ciągu 14 dni.\n\nPozdrawiamy,\nZarząd'),

  (NULL, 'license_expiry',  'Licencja wkrótce wygasa',
   'Twoja licencja {license_type} wygasa za {days} dni',
   'Cześć {first_name},\n\nTwoja licencja {license_type} (numer: {license_number}) wygasa {valid_until}.\nPozostało {days} dni. Prosimy o odnowienie w terminie.\n\nPozdrawiamy,\nKlub {club_name}'),

  (NULL, 'medical_expiry',  'Badanie lekarskie wygasa',
   'Twoje badanie lekarskie wygasa za {days} dni',
   'Cześć {first_name},\n\nTwoje badanie lekarskie wygasa {valid_until}.\nPozostało {days} dni. Prosimy o wykonanie nowego badania.\n\nPozdrawiamy,\nKlub {club_name}'),

  (NULL, 'event_reminder',  'Przypomnienie o wydarzeniu',
   'Przypomnienie: {event_name} — {event_date}',
   'Cześć {first_name},\n\nPrzypominamy o zbliżającym się wydarzeniu:\n{event_name} — {event_date}, {event_location}\n\nPozdrawiamy,\nKlub {club_name}'),

  (NULL, 'password_reset',  'Reset hasła',
   'Reset hasła w portalu {club_name}',
   'Cześć {first_name},\n\nWygenerowano link do resetu hasła. Kliknij poniższy link w ciągu 1 godziny:\n\n{reset_link}\n\nJeśli nie prosiłeś/aś o reset, zignoruj tę wiadomość.\n\nPozdrawiamy,\nKlub {club_name}');

SET foreign_key_checks = 1;
