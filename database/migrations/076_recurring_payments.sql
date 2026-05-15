-- 076_recurring_payments.sql
-- Recurring payments — Stripe Subscriptions + Przelewy24 cyclic payments.
-- Każda subskrypcja składki członkowskiej jest scoped per klub i przypięta
-- do credentials klubu w club_payment_gateways (PR #106 multi-tenant).
--
-- Tabele:
--   - member_subscriptions  → header subskrypcji + status FSM
--   - subscription_charges  → audit log każdej próby chargu (success/failed)

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `member_subscriptions` (
    `id`                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`                     INT UNSIGNED NOT NULL,
    `member_id`                   INT UNSIGNED NOT NULL,
    `fee_rate_id`                 INT UNSIGNED NOT NULL,
    `gateway_provider`            ENUM('stripe','przelewy24') NOT NULL,
    `external_customer_id`        VARCHAR(120) NULL COMMENT 'Stripe customer cus_xxx / P24 client ID',
    `external_subscription_id`    VARCHAR(120) NULL COMMENT 'Stripe sub_xxx / P24 recurring template ID',
    `external_payment_method_id`  VARCHAR(120) NULL COMMENT 'Stripe pm_xxx',
    `external_price_id`           VARCHAR(120) NULL COMMENT 'Stripe price_xxx',
    `setup_session_id`            VARCHAR(120) NULL COMMENT 'Checkout session ID dla return-handling',
    `amount`                      DECIMAL(10,2) NOT NULL,
    `currency`                    CHAR(3) NOT NULL DEFAULT 'PLN',
    `billing_period`              ENUM('monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
    `status`                      ENUM('pending_setup','active','paused','cancelled','past_due','expired') NOT NULL DEFAULT 'pending_setup',
    `current_period_start`        DATETIME NULL,
    `current_period_end`          DATETIME NULL,
    `next_charge_at`              DATETIME NULL,
    `cancelled_at`                DATETIME NULL,
    `cancellation_reason`         VARCHAR(255) NULL,
    `last_payment_at`             DATETIME NULL,
    `last_payment_status`         VARCHAR(40) NULL,
    `failed_charges_count`        INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_external_sub` (`gateway_provider`, `external_subscription_id`),
    KEY `idx_member`              (`member_id`),
    KEY `idx_club_status`         (`club_id`, `status`),
    KEY `idx_status_next_charge`  (`status`, `next_charge_at`),
    KEY `idx_setup_session`       (`setup_session_id`),
    CONSTRAINT `fk_msub_club`     FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)      ON DELETE CASCADE,
    CONSTRAINT `fk_msub_member`   FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_msub_feerate`  FOREIGN KEY (`fee_rate_id`) REFERENCES `fee_rates`(`id`)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Subskrypcje cykliczne składek (Stripe Subscriptions / P24 recurring)';

CREATE TABLE IF NOT EXISTS `subscription_charges` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`             INT UNSIGNED NOT NULL,
    `subscription_id`     INT UNSIGNED NOT NULL,
    `external_payment_id` VARCHAR(120) NULL COMMENT 'Stripe in_xxx / pi_xxx / P24 sessionId',
    `external_invoice_id` VARCHAR(120) NULL COMMENT 'Stripe invoice ID',
    `amount`              DECIMAL(10,2) NOT NULL,
    `currency`            CHAR(3) NOT NULL DEFAULT 'PLN',
    `status`              ENUM('pending','succeeded','failed','refunded') NOT NULL DEFAULT 'pending',
    `failure_reason`      VARCHAR(500) NULL,
    `period_start`        DATETIME NULL,
    `period_end`          DATETIME NULL,
    `charged_at`          DATETIME NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_subscription` (`subscription_id`),
    KEY `idx_external`     (`external_payment_id`),
    KEY `idx_club_status`  (`club_id`, `status`),
    CONSTRAINT `fk_subch_club` FOREIGN KEY (`club_id`)         REFERENCES `clubs`(`id`)                ON DELETE CASCADE,
    CONSTRAINT `fk_subch_sub`  FOREIGN KEY (`subscription_id`) REFERENCES `member_subscriptions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit log poszczególnych chargeów subskrypcji (per okres rozliczeniowy)';

SET foreign_key_checks = 1;
