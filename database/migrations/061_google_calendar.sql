-- ============================================================
-- Migracja 061_google_calendar.sql
--
-- Per-klub integracja z Google Calendar (OAuth2 + Calendar API v3).
--
-- Każdy klub łączy WŁASNE konto Google Workspace / Gmail przez OAuth2.
-- Tokeny (access + refresh) szyfrowane AES-256-GCM przez
-- App\Helpers\Encryption.
--
-- Wzorzec analogiczny do ClubPaymentGatewayModel / ClubShippingProviderModel.
--
-- Tabela synca: korzystamy z `calendar_events` (a nie `events`) bo to
-- generyczna tabela wpisów kalendarza klubu (start_at/end_at/title/...
-- pasują 1:1 do Google Event resource).
-- ============================================================

SET foreign_key_checks = 0;

-- ── Konfiguracja połączenia Google Calendar per klub ──────────
CREATE TABLE IF NOT EXISTS `club_google_calendar` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`               INT UNSIGNED NOT NULL,
    `google_account_email`  VARCHAR(120) NULL
                            COMMENT 'Konto Google z którego pochodzi token (do UI)',
    `calendar_id`           VARCHAR(120) NULL
                            COMMENT '"primary" lub konkretny calendarId z listy',
    `client_id`             VARCHAR(255) NULL
                            COMMENT 'OAuth client_id (per klub white-label) — pusty = użyj globalnego z config/google.php',
    `client_secret_enc`     TEXT NULL COMMENT 'AES-256-GCM',
    `access_token_enc`      TEXT NULL,
    `refresh_token_enc`     TEXT NULL,
    `token_expires_at`      DATETIME NULL,
    `sync_direction`        ENUM('push','pull','both') NOT NULL DEFAULT 'push',
    `last_sync_at`          DATETIME NULL,
    `last_sync_status`      VARCHAR(40) NULL,
    `last_sync_message`     VARCHAR(500) NULL,
    `is_active`             TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-klub OAuth2 + sync state dla Google Calendar';

-- ── Mapowanie lokalnych eventów ↔ Google Calendar event IDs ───
CREATE TABLE IF NOT EXISTS `event_google_mapping` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`           INT UNSIGNED NOT NULL,
    `event_id`          INT UNSIGNED NOT NULL
                        COMMENT 'calendar_events.id',
    `google_event_id`   VARCHAR(120) NOT NULL,
    `etag`              VARCHAR(160) NULL,
    `last_synced_at`    DATETIME NULL,
    `sync_status`       ENUM('synced','out_of_date','error') NOT NULL DEFAULT 'synced',
    `last_error`        VARCHAR(500) NULL,
    UNIQUE KEY `uniq_event` (`event_id`),
    KEY `idx_google_event` (`google_event_id`),
    KEY `idx_club` (`club_id`),
    FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`)           ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `calendar_events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Mapowanie calendar_events.id ↔ Google Calendar event ID';

SET foreign_key_checks = 1;
