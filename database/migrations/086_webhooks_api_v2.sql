-- 086: Webhook subscriptions + Public API v2
--
-- Trzy nowe tabele:
--   * webhook_subscriptions — endpointy klubow subskrybujace eventy systemowe;
--                              dispatcher czyta active=1 i typ eventu z `event_types`.
--   * webhook_deliveries    — queue + audit log proby/dostawy webhookow;
--                              worker (cli/webhook_worker.php) bierze pending/retrying
--                              i probuje wyslac z exponential backoff (max 5 prob).
--   * api_v2_tokens         — Public API v2 (bearer token) dla integracji zewnetrznych.
--                              Plain token pokazany RAZ przy utworzeniu, DB trzyma SHA-256.
--
-- Wszystkie multi-tenant przez club_id FK -> clubs(id) ON DELETE CASCADE.

CREATE TABLE IF NOT EXISTS webhook_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    target_url VARCHAR(500) NOT NULL,
    secret CHAR(64) NOT NULL,
    event_types JSON NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_success_at DATETIME NULL,
    last_failure_at DATETIME NULL,
    failure_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_club_active (club_id, active),
    CONSTRAINT fk_webhook_sub_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS webhook_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    status ENUM('pending','delivered','failed','retrying') NOT NULL DEFAULT 'pending',
    http_status INT NULL,
    response_body TEXT NULL,
    attempts TINYINT NOT NULL DEFAULT 0,
    next_retry_at DATETIME NULL,
    delivered_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status_retry (status, next_retry_at),
    KEY idx_sub_created (subscription_id, created_at),
    CONSTRAINT fk_webhook_delivery_sub FOREIGN KEY (subscription_id) REFERENCES webhook_subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS api_v2_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    scopes JSON NOT NULL,
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_token (token_hash),
    KEY idx_club (club_id),
    CONSTRAINT fk_api_v2_token_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
