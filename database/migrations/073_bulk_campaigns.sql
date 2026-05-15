-- 073_bulk_campaigns.sql
-- Bulk operations: email/SMS marketing campaigns dla zarządu klubu.
-- Pozwala wysłać masowy mailing/SMS do filtrowanej grupy członków,
-- z trackingiem statusów per odbiorca i podsumowaniem statystyk.

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `campaigns` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`           INT UNSIGNED NOT NULL,
    `name`              VARCHAR(120) NOT NULL,
    `channel`           ENUM('email','sms','both') NOT NULL,
    `template_subject`  VARCHAR(200) NULL,
    `template_body`     TEXT NOT NULL,
    `recipients_filter` JSON NULL COMMENT 'snapshot filter: sport_id, age_min, age_max, status, etc.',
    `recipients_count`  INT UNSIGNED NOT NULL DEFAULT 0,
    `sent_count`        INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_count`      INT UNSIGNED NOT NULL DEFAULT 0,
    `status`            ENUM('draft','scheduled','sending','sent','failed') NOT NULL DEFAULT 'draft',
    `scheduled_at`      DATETIME NULL,
    `sent_at`           DATETIME NULL,
    `created_by`        INT UNSIGNED NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_club_status` (`club_id`, `status`),
    KEY `idx_scheduled`   (`status`, `scheduled_at`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Masowe kampanie email/SMS — snapshot filtra + statystyki';

CREATE TABLE IF NOT EXISTS `campaign_recipients` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `campaign_id`    INT UNSIGNED NOT NULL,
    `member_id`      INT UNSIGNED NOT NULL,
    `channel`        ENUM('email','sms') NOT NULL,
    `to_address`     VARCHAR(255) NOT NULL COMMENT 'email lub numer telefonu',
    `status`         ENUM('queued','sent','failed','bounced') NOT NULL DEFAULT 'queued',
    `error_message`  VARCHAR(500) NULL,
    `sent_at`        DATETIME NULL,
    KEY `idx_campaign` (`campaign_id`),
    KEY `idx_member`   (`member_id`),
    KEY `idx_status`   (`status`),
    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pojedynczy odbiorca kampanii + jego status';

SET foreign_key_checks = 1;
