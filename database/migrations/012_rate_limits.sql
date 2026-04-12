-- Migration 012: Rate limiting
-- Protects login and other sensitive endpoints from brute-force attacks

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip`              VARCHAR(45)  NOT NULL,
  `action`          VARCHAR(60)  NOT NULL,
  `attempts`        INT UNSIGNED NOT NULL DEFAULT 0,
  `last_attempt_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blocked_until`   DATETIME     NULL,
  UNIQUE KEY `uq_ip_action` (`ip`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rate limiting — ochrona przed brute-force';
