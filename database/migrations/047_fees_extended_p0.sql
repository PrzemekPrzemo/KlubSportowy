-- ============================================================
-- Migracja 047_fees_extended_p0.sql
--
-- Faza P.0 — fundacja rozszerzonego modułu opłat:
--   - fee_discounts          — zniżki (% + kwota stała + warunki JSON)
--   - member_fee_assignments — przypisanie polityki opłat do zawodnika
--   - payment_dues           — należności (auto-generowane lub ręczne)
--   - club_payment_gateways  — per-club API credentials (Przelewy24, PayU, Stripe)
--   - rozszerzenie payments: due_id, status, invoice_number
--
-- Wszystkie nowe tabele MAJĄ club_id z FK CASCADE — pełna izolacja per klub.
-- ============================================================

SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- fee_discounts — zniżki klubowe
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fee_discounts` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`      INT UNSIGNED NOT NULL,
    `code`         VARCHAR(40)  NOT NULL COMMENT 'unikalny per klub: rodzinny, junior, multisport, scholarship',
    `name`         VARCHAR(120) NOT NULL,
    `discount_type` ENUM('percent','fixed_amount') NOT NULL DEFAULT 'percent',
    `value`        DECIMAL(10,2) NOT NULL COMMENT 'percent: 0-100, fixed_amount: kwota PLN',
    `description`  TEXT NULL,
    `conditions`   JSON NULL COMMENT 'warunki auto-stosowania: {min_active_sports:2} | {age_max:18} | {family_min_members:2}',
    `is_stackable` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=można łączyć z innymi zniżkami',
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `valid_from`   DATE NULL,
    `valid_to`     DATE NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_discount_club_code` (`club_id`, `code`),
    KEY `idx_discount_club_active` (`club_id`, `is_active`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zniżki/rabaty klubowe — % lub kwota stała, warunki JSON';

-- ------------------------------------------------------------
-- member_fee_assignments — przypisanie zawodnika do polityki opłat
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_fee_assignments` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`      INT UNSIGNED NOT NULL,
    `member_id`    INT UNSIGNED NOT NULL,
    `fee_rate_id`  INT UNSIGNED NOT NULL,
    `valid_from`   DATE NOT NULL DEFAULT (CURRENT_DATE),
    `valid_to`     DATE NULL COMMENT 'NULL = bezterminowo',
    `status`       ENUM('active','suspended','ended') NOT NULL DEFAULT 'active',
    `notes`        TEXT NULL,
    `created_by`   INT UNSIGNED NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_mfa_club_member` (`club_id`, `member_id`),
    KEY `idx_mfa_active` (`status`, `valid_from`, `valid_to`),
    FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)      ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`fee_rate_id`) REFERENCES `fee_rates`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Subskrypcje opłat — kto co płaci i od kiedy';

-- ------------------------------------------------------------
-- member_fee_assignment_discounts — łączenie zniżek (M:N)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_fee_assignment_discounts` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `assignment_id` INT UNSIGNED NOT NULL,
    `discount_id`   INT UNSIGNED NOT NULL,
    `applied_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `applied_by`    INT UNSIGNED NULL,
    UNIQUE KEY `uq_assignment_discount` (`assignment_id`, `discount_id`),
    FOREIGN KEY (`assignment_id`) REFERENCES `member_fee_assignments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`discount_id`)   REFERENCES `fee_discounts`(`id`)          ON DELETE CASCADE,
    FOREIGN KEY (`applied_by`)    REFERENCES `users`(`id`)                  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Łączenie wielu zniżek per assignment (stackable)';

-- ------------------------------------------------------------
-- payment_dues — należności (kto powinien zapłacić ile za jaki okres)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_dues` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`       INT UNSIGNED NOT NULL,
    `member_id`     INT UNSIGNED NOT NULL,
    `assignment_id` INT UNSIGNED NULL COMMENT 'auto-generowane z assignment, NULL = ręczne',
    `fee_rate_id`   INT UNSIGNED NULL,
    `period_year`   YEAR NOT NULL,
    `period_month`  TINYINT UNSIGNED NULL COMMENT 'NULL = roczna',
    `gross_amount`  DECIMAL(10,2) NOT NULL COMMENT 'kwota przed zniżkami',
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'sumaryczna zniżka',
    `net_amount`    DECIMAL(10,2) NOT NULL COMMENT 'kwota do zapłaty',
    `paid_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `due_date`      DATE NOT NULL,
    `status`        ENUM('pending','partial','paid','overdue','waived','cancelled') NOT NULL DEFAULT 'pending',
    `discount_breakdown` JSON NULL COMMENT '[{discount_id, code, amount}, ...]',
    `notes`         TEXT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_dues_member_period` (`club_id`, `member_id`, `fee_rate_id`, `period_year`, `period_month`),
    KEY `idx_dues_status_due` (`status`, `due_date`),
    KEY `idx_dues_club_period` (`club_id`, `period_year`, `period_month`),
    FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)                 ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)               ON DELETE CASCADE,
    FOREIGN KEY (`assignment_id`) REFERENCES `member_fee_assignments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`fee_rate_id`)   REFERENCES `fee_rates`(`id`)             ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Należności — auto-generowane z assignment lub ręczne';

-- ------------------------------------------------------------
-- club_payment_gateways — per-klub API credentials
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `club_payment_gateways` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NOT NULL,
    `provider`        ENUM('przelewy24','payu','stripe','tpay','manual') NOT NULL,
    `is_active`       TINYINT(1) NOT NULL DEFAULT 0,
    `is_sandbox`      TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=test, 0=produkcja',
    `merchant_id`     VARCHAR(120) NULL COMMENT 'POS ID / Merchant ID',
    `api_key`         VARCHAR(500) NULL COMMENT 'AES-256-GCM encrypted',
    `api_secret`      VARCHAR(500) NULL COMMENT 'AES-256-GCM encrypted',
    `crc_key`         VARCHAR(500) NULL COMMENT 'AES-256-GCM encrypted (Przelewy24-specific CRC)',
    `webhook_secret`  VARCHAR(500) NULL COMMENT 'AES-256-GCM encrypted',
    `return_url`      VARCHAR(255) NULL,
    `notify_url`      VARCHAR(255) NULL,
    `currency`        CHAR(3) NOT NULL DEFAULT 'PLN',
    `notes`           TEXT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_club_provider` (`club_id`, `provider`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-klub API credentials dla bramek płatności (Przelewy24, PayU itd.)';

-- ------------------------------------------------------------
-- payments — rozszerzenie o due_id, status, invoice_number
-- ------------------------------------------------------------
ALTER TABLE `payments`
    ADD COLUMN IF NOT EXISTS `due_id` INT UNSIGNED NULL AFTER `fee_rate_id`,
    ADD COLUMN IF NOT EXISTS `status` ENUM('completed','partial','refund','cancelled') NOT NULL DEFAULT 'completed' AFTER `method`,
    ADD COLUMN IF NOT EXISTS `invoice_number` VARCHAR(40) NULL AFTER `reference`,
    ADD KEY IF NOT EXISTS `idx_payments_due` (`due_id`),
    ADD KEY IF NOT EXISTS `idx_payments_invoice` (`invoice_number`);

-- FK do payment_dues (osobno — IF NOT EXISTS dla ALTER nie obsługuje constraints w niektórych MySQL).
-- Uruchamiamy tolerancyjnie: błąd "Duplicate foreign key" po drugim wykonaniu jest OK.
ALTER TABLE `payments`
    ADD CONSTRAINT `fk_payments_due_id`
    FOREIGN KEY (`due_id`) REFERENCES `payment_dues`(`id`) ON DELETE SET NULL;

SET foreign_key_checks = 1;
