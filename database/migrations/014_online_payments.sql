-- Online payments for member portal
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `online_payments` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`       INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `fee_rate_id`   INT UNSIGNED NULL,
  `amount`        DECIMAL(10,2) NOT NULL,
  `currency`      CHAR(3) NOT NULL DEFAULT 'PLN',
  `description`   VARCHAR(200) NOT NULL,
  `period_year`   YEAR NULL,
  `period_month`  TINYINT UNSIGNED NULL,
  `provider`      ENUM('stripe','przelewy24','tpay','manual') NOT NULL DEFAULT 'stripe',
  `provider_id`   VARCHAR(255) NULL COMMENT 'ID transakcji u providera',
  `checkout_url`  VARCHAR(500) NULL,
  `status`        ENUM('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `paid_at`       DATETIME NULL,
  `ip_address`    VARCHAR(45) NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_op_club`   (`club_id`),
  KEY `idx_op_member` (`member_id`),
  KEY `idx_op_status` (`status`),
  FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`fee_rate_id`) REFERENCES `fee_rates`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Platnosci online z portalu zawodnika';

SET foreign_key_checks = 1;
