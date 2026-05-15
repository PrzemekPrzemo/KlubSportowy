-- 071_member_api_tokens.sql
-- Mobile API v1 — long-lived bearer tokens for the ClubDesk mobile app.
-- Each row is one device/session. Raw tokens are NEVER stored in DB —
-- only SHA-256 hashes. Tokens are revocable (revoked_at) and time-bounded.

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `member_api_tokens` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `member_id`           INT UNSIGNED NOT NULL,
    `club_id`             INT UNSIGNED NOT NULL,
    `identity_id`         INT UNSIGNED NULL COMMENT 'when login was via member_identities',
    `token_hash`          CHAR(64) NOT NULL COMMENT 'SHA-256 of raw access token; raw never in DB',
    `refresh_token_hash`  CHAR(64) NULL COMMENT 'SHA-256 of raw refresh token',
    `device_info`         VARCHAR(255) NULL,
    `user_agent`          VARCHAR(500) NULL,
    `ip_address`          VARCHAR(45) NULL,
    `app_version`         VARCHAR(20) NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_used_at`        DATETIME NULL,
    `expires_at`          DATETIME NOT NULL,
    `refresh_expires_at`  DATETIME NULL,
    `revoked_at`          DATETIME NULL,
    UNIQUE KEY `uq_token_hash` (`token_hash`),
    KEY `idx_refresh_hash` (`refresh_token_hash`),
    KEY `idx_member` (`member_id`),
    KEY `idx_active` (`revoked_at`, `expires_at`),
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Mobile API access + refresh tokens (Mobile API v1)';

-- Extend device_tokens with mobile-app metadata used by /push/register.
-- All columns nullable so legacy rows remain valid.
ALTER TABLE `device_tokens`
    ADD COLUMN `app_version`  VARCHAR(20)  NULL AFTER `platform`,
    ADD COLUMN `device_model` VARCHAR(100) NULL AFTER `app_version`;

SET foreign_key_checks = 1;
