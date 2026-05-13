-- ============================================================
-- 058_federation_credentials.sql
-- FederationExporter — per-klub credentials + export log.
--
-- club_federation_credentials:
--   Per-klub credentials do API/portali federacji (PZPN/PZSS/PZKosz/PZLA…).
--   Pola api_username/api_password/api_token są ZASZYFROWANE (AES-256-GCM,
--   App\Helpers\Encryption). Decrypt-on-read tylko gdy faktycznie używamy
--   do API call'a — wzór z ClubPaymentGatewayModel.
--
-- federation_export_log:
--   Audit każdej operacji exportera (register/update/license_renew/…).
--   Bulk export → member_id=NULL.
-- ============================================================

CREATE TABLE IF NOT EXISTS `club_federation_credentials` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`            INT UNSIGNED NOT NULL,
    `federation_code`    VARCHAR(40)  NOT NULL COMMENT 'PZPN/PZSS/PZKosz/PZLA/PZPS/etc.',
    `is_sandbox`         TINYINT(1)   NOT NULL DEFAULT 1,
    `api_username_enc`   TEXT         NULL,
    `api_password_enc`   TEXT         NULL,
    `api_token_enc`      TEXT         NULL,
    `organization_id`    VARCHAR(60)  NULL COMMENT 'np. numer klubu w systemie federacji (PZPN club_id, PZSS numer)',
    `notes`              VARCHAR(500) NULL,
    `is_active`          TINYINT(1)   NOT NULL DEFAULT 0,
    `last_export_at`     DATETIME     NULL,
    `last_export_status` VARCHAR(40)  NULL,
    `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_club_federation` (`club_id`, `federation_code`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-klub credentials do federacji sportowych (zaszyfrowane).';

CREATE TABLE IF NOT EXISTS `federation_export_log` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`          INT UNSIGNED NOT NULL,
    `federation_code`  VARCHAR(40)  NOT NULL,
    `member_id`        INT UNSIGNED NULL COMMENT 'NULL gdy bulk export',
    `operation`        VARCHAR(40)  NOT NULL COMMENT 'register / update / license_renew / status_fetch / test',
    `status`           ENUM('queued','success','failed') NOT NULL DEFAULT 'queued',
    `request_payload`  JSON         NULL,
    `response_payload` JSON         NULL,
    `error_message`    VARCHAR(1000) NULL,
    `triggered_by`     INT UNSIGNED NULL COMMENT 'users.id (admin który uruchomił)',
    `triggered_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at`     DATETIME     NULL,
    KEY `idx_club_fed` (`club_id`, `federation_code`),
    KEY `idx_status` (`status`),
    KEY `idx_member` (`member_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit log eksportów do federacji (register / update / license).';
