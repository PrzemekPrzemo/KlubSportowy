-- 088: KSeF (Krajowy System e-Faktur) — Phase 1 foundation.
--
-- Per-club configuration for the Polish national e-invoice system.
-- Phase 1 (this migration): config + audit log only. No invoices yet.
-- Phase 2 (later): club_invoices table + XML generator.
-- Phase 3 (later): XAdES signing + send queue.
--
-- Multi-tenant: row per club via UNIQUE(club_id) + ON DELETE CASCADE.
-- Sensitive values (api_token, cert_password) stored encrypted via
-- App\Helpers\Encryption::encryptForClub(plaintext, club_id) so even a DB
-- dump of one club cannot decrypt secrets of another club without
-- (master key + that club's HKDF context).

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `club_ksef_config` (
    `id`                              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`                         INT UNSIGNED NOT NULL UNIQUE,
    `enabled`                         TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Super admin toggle — feature flag',
    `mode`                            ENUM('test','prod') NOT NULL DEFAULT 'test',
    `nip`                             CHAR(10) NULL COMMENT 'NIP klubu w formacie 1234567890 bez kresek',
    `api_token_encrypted`             TEXT NULL COMMENT 'Token KSeF, zaszyfrowany przez Encryption::encryptForClub',
    `cert_path`                       VARCHAR(500) NULL COMMENT 'Sciezka do .p12 / .pfx w storage/ksef/{club_id}/',
    `cert_password_encrypted`         TEXT NULL,
    `authorized_subject_identifier`   VARCHAR(50) NULL COMMENT 'identifier dla SessionToken',
    `last_connection_test_at`         DATETIME NULL,
    `last_connection_test_status`     ENUM('ok','failed','never') NOT NULL DEFAULT 'never',
    `last_connection_test_message`    TEXT NULL,
    `created_at`                      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ksef_config_club`
        FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ksef_audit_log` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`    INT UNSIGNED NOT NULL,
    `action`     ENUM('config_change','enabled','disabled','connection_test','token_set','cert_uploaded') NOT NULL,
    `user_id`    INT UNSIGNED NULL,
    `details`    TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_club_action` (`club_id`, `action`),
    KEY `idx_created_at`  (`created_at`),
    CONSTRAINT `fk_ksef_audit_club`
        FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
