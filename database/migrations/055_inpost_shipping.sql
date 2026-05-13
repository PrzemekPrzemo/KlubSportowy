-- ============================================================
-- Migracja 055_inpost_shipping.sql
--
-- Integracja InPost (Paczkomaty + Kurier) przez ShipX API v1.
--
-- Każdy klub ma WŁASNE credentials InPost (token + organization_id) — to
-- pozwala klubom korzystać z własnych umów handlowych / cenników InPost.
-- Pola wrażliwe (api_token, organization_id) szyfrowane AES-256-GCM przez
-- App\Helpers\Encryption.
--
-- Wzorzec: bliźniaczy do club_payment_gateways (055 → P.5 + F.6).
-- ============================================================

CREATE TABLE club_shipping_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    provider ENUM('inpost') NOT NULL DEFAULT 'inpost',
    is_sandbox TINYINT(1) NOT NULL DEFAULT 1,
    organization_id_enc TEXT NULL,   -- AES-256-GCM
    api_token_enc TEXT NULL,         -- AES-256-GCM
    default_size ENUM('A','B','C') NOT NULL DEFAULT 'A',
    default_service VARCHAR(60) NOT NULL DEFAULT 'inpost_locker_standard',
    sender_name VARCHAR(120) NULL,
    sender_email VARCHAR(120) NULL,
    sender_phone VARCHAR(20) NULL,
    sender_address_street VARCHAR(120) NULL,
    sender_address_building VARCHAR(20) NULL,
    sender_address_city VARCHAR(80) NULL,
    sender_address_post_code VARCHAR(10) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_club_provider (club_id, provider),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    provider VARCHAR(20) NOT NULL DEFAULT 'inpost',
    external_id VARCHAR(60) NULL,
    tracking_number VARCHAR(60) NULL,
    label_url VARCHAR(500) NULL,
    recipient_name VARCHAR(120),
    recipient_email VARCHAR(120),
    recipient_phone VARCHAR(20),
    target_locker_id VARCHAR(20) NULL,
    size CHAR(1) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'created',
    cost_amount DECIMAL(10,2) NULL,
    member_id INT UNSIGNED NULL,
    internal_note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_club_status (club_id, status),
    KEY idx_member (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
