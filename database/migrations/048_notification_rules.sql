-- ============================================================
-- Migracja 048_notification_rules.sql
--
-- Faza S.0 — fundacja systemu przypomnień email/SMS.
--
-- Tabele:
--   notification_rules   — kiedy słać przypomnienia (per klub, per template)
--   notification_log     — audit log: kto/kiedy/dlaczego (rate limiting + audyt)
--   member_notification_prefs — opt-out per zawodnik (RODO compliance)
-- ============================================================

SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- notification_rules — konfiguracja kiedy słać przypomnienia
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_rules` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NOT NULL,
    `template_type`   VARCHAR(80) NOT NULL COMMENT 'fee_reminder|license_expiry|medical_expiry|...',
    `trigger_event`   ENUM('days_after_due','days_before_expiry','immediate') NOT NULL DEFAULT 'days_after_due',
    `days_offset`     SMALLINT NOT NULL DEFAULT 7 COMMENT 'liczba dni od/do zdarzenia',
    `channel`         ENUM('email','sms','both') NOT NULL DEFAULT 'email',
    `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
    `max_per_target`  TINYINT UNSIGNED NOT NULL DEFAULT 1
                      COMMENT 'max ile razy ten sam target moze dostac (anti-spam)',
    `notes`           TEXT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_rule_club_tpl_offset` (`club_id`, `template_type`, `trigger_event`, `days_offset`),
    KEY `idx_rules_active` (`is_active`, `template_type`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Reguły kiedy słać przypomnienia (per klub, per template_type)';

-- ------------------------------------------------------------
-- notification_log — audit kto/kiedy/dlaczego
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_log` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NOT NULL,
    `member_id`       INT UNSIGNED NULL COMMENT 'NULL = wysłane do nie-czlonka (np. zarząd)',
    `template_type`   VARCHAR(80) NOT NULL,
    `target_type`     VARCHAR(40) NOT NULL DEFAULT 'payment_due'
                      COMMENT 'payment_due | license | medical | event | ad-hoc',
    `target_id`       INT UNSIGNED NULL COMMENT 'id w tabeli targetowanej (np. payment_dues.id)',
    `channel`         ENUM('email','sms') NOT NULL DEFAULT 'email',
    `recipient`       VARCHAR(255) NOT NULL COMMENT 'email lub numer telefonu',
    `email_queue_id`  INT UNSIGNED NULL COMMENT 'reference do queue gdy email',
    `status`          ENUM('queued','sent','failed','suppressed') NOT NULL DEFAULT 'queued',
    `error`           TEXT NULL,
    `rule_id`         INT UNSIGNED NULL COMMENT 'rule ktora wystrzelila (NULL = manualne)',
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_nlog_member`  (`member_id`, `template_type`),
    KEY `idx_nlog_target`  (`target_type`, `target_id`),
    KEY `idx_nlog_club_at` (`club_id`, `created_at`),
    FOREIGN KEY (`club_id`)         REFERENCES `clubs`(`id`)               ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)       REFERENCES `members`(`id`)             ON DELETE SET NULL,
    FOREIGN KEY (`email_queue_id`)  REFERENCES `email_queue`(`id`)         ON DELETE SET NULL,
    FOREIGN KEY (`rule_id`)         REFERENCES `notification_rules`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit log wysłanych przypomnień — chroni przed spam i ułatwia raportowanie';

-- ------------------------------------------------------------
-- member_notification_prefs — opt-out per zawodnik (RODO)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_notification_prefs` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `member_id`     INT UNSIGNED NOT NULL,
    `club_id`       INT UNSIGNED NOT NULL,
    `template_type` VARCHAR(80) NULL COMMENT 'NULL = global opt-out (wszystkie powiadomienia)',
    `channel`       ENUM('email','sms','both') NOT NULL DEFAULT 'both',
    `opted_out`     TINYINT(1) NOT NULL DEFAULT 0,
    `notes`         TEXT NULL,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_pref_member_tpl` (`member_id`, `template_type`),
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Opt-out per zawodnik — RODO + UX (zawodnik może wyciszyć powiadomienia)';

-- ------------------------------------------------------------
-- Domyślne reguły dla istniejących klubów (one-time backfill)
-- Każdy klub dostaje 3 reguły dla fee_reminder: 3, 7, 14 dni po terminie
-- ------------------------------------------------------------
INSERT IGNORE INTO notification_rules
    (club_id, template_type, trigger_event, days_offset, channel, is_active, max_per_target, notes)
SELECT id, 'fee_reminder', 'days_after_due', 3,  'email', 1, 1, 'Auto-utworzona reguła startowa' FROM clubs;

INSERT IGNORE INTO notification_rules
    (club_id, template_type, trigger_event, days_offset, channel, is_active, max_per_target, notes)
SELECT id, 'fee_reminder', 'days_after_due', 7,  'email', 1, 1, 'Auto-utworzona reguła startowa' FROM clubs;

INSERT IGNORE INTO notification_rules
    (club_id, template_type, trigger_event, days_offset, channel, is_active, max_per_target, notes)
SELECT id, 'fee_reminder', 'days_after_due', 14, 'email', 1, 1, 'Auto-utworzona reguła startowa' FROM clubs;

SET foreign_key_checks = 1;
