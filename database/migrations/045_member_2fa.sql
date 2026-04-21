-- S3: 2FA TOTP dla zawodników (portal)
-- Gdy w systemie są dane medyczne, członkowie muszą mieć opcję 2FA.

SET foreign_key_checks = 0;

ALTER TABLE members
    ADD COLUMN IF NOT EXISTS totp_enabled      TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS totp_secret       VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS totp_confirmed_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS member_totp_backup_codes (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id  INT UNSIGNED NOT NULL,
    code_hash  VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    used_at    DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_mtbc_member` (`member_id`),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
