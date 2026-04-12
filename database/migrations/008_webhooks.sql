-- ============================================================
-- 008: Webhooks (endpointy + logi)
-- ============================================================

CREATE TABLE IF NOT EXISTS `webhook_endpoints` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `url`        VARCHAR(500) NOT NULL,
  `secret`     VARCHAR(255) NOT NULL,
  `events`     JSON NOT NULL COMMENT '["member.created","payment.received"]',
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Webhook endpointy per klub';

CREATE TABLE IF NOT EXISTS `webhook_log` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `endpoint_id`   INT UNSIGNED NOT NULL,
  `event`         VARCHAR(80) NOT NULL,
  `payload`       JSON NULL,
  `response_code` SMALLINT NULL,
  `response_body` TEXT NULL,
  `sent_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`endpoint_id`) REFERENCES `webhook_endpoints`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log wyslanych webhookow';
