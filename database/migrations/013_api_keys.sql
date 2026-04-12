-- API keys for REST API v1
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `name`        VARCHAR(100) NOT NULL COMMENT 'opis klucza (np. "Strona WWW", "Mobilna apka")',
  `key_hash`    VARCHAR(255) NOT NULL COMMENT 'bcrypt hash klucza',
  `key_prefix`  VARCHAR(10)  NOT NULL COMMENT 'pierwsze 8 znaków (do identyfikacji)',
  `scopes`      JSON         NULL COMMENT '["members:read","events:read","payments:read"]',
  `rate_limit`  INT UNSIGNED NOT NULL DEFAULT 60 COMMENT 'max żądań/minutę',
  `last_used_at` DATETIME    NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ak_club` (`club_id`),
  UNIQUE KEY `uq_ak_prefix` (`key_prefix`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Klucze API per klub do REST API v1';

SET foreign_key_checks = 1;
