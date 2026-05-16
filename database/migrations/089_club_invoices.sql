-- 089: KSeF Phase 2 — Club sales invoices + KSeF FA(2) numbering.
--
-- Phase 1 (#088) introduced per-club KSeF configuration (token, cert, mode).
-- Phase 2 (this migration) introduces SALES INVOICES issued by the club:
--   * club_invoices            — header (one row per invoice)
--   * club_invoice_items       — line items (1..N per invoice)
--   * club_invoice_numbering   — per-club, per-year monotonic sequence
--
-- Multi-tenant: every table is scoped by club_id (FK CASCADE).
-- Numbering: row-level lock via INSERT ... ON DUPLICATE KEY UPDATE +
--            LAST_INSERT_ID(next_sequence) — atomic across racing inserts.
--
-- KSeF wiring is left at the column level (status, ksef_*). Sending the
-- XML to KSeF is Phase 3 (queue + XAdES signing).

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `club_invoices` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`          INT UNSIGNED NOT NULL,
    `invoice_number`   VARCHAR(50) NOT NULL,
    `invoice_type`     ENUM('VAT','VAT_korekta','VAT_RR','proforma','paragon') NOT NULL DEFAULT 'VAT',

    -- Strony
    `seller_name`      VARCHAR(200) NOT NULL,
    `seller_nip`       CHAR(10)     NOT NULL,
    `seller_address`   TEXT         NULL,
    `buyer_member_id`  INT UNSIGNED NULL COMMENT 'jesli faktura dla czlonka klubu',
    `buyer_name`       VARCHAR(200) NOT NULL,
    `buyer_nip`        CHAR(10)     NULL COMMENT 'B2C: NULL, B2B: NIP',
    `buyer_address`    TEXT         NULL,
    `buyer_email`      VARCHAR(255) NULL,

    -- Daty
    `issue_date`       DATE NOT NULL,
    `sale_date`        DATE NOT NULL,
    `due_date`         DATE NULL,

    -- Kwoty (PLN)
    `total_net`        DECIMAL(12,2) NOT NULL DEFAULT 0,
    `total_vat`        DECIMAL(12,2) NOT NULL DEFAULT 0,
    `total_gross`      DECIMAL(12,2) NOT NULL DEFAULT 0,
    `currency`         CHAR(3) NOT NULL DEFAULT 'PLN',
    `exchange_rate`    DECIMAL(10,4) NULL,

    -- Status workflow: draft -> issued -> sent_ksef -> accepted_ksef | rejected_ksef
    `status`           ENUM('draft','issued','sent_ksef','accepted_ksef','rejected_ksef','cancelled')
                       NOT NULL DEFAULT 'draft',
    `ksef_session_id`        VARCHAR(100) NULL,
    `ksef_reference_number`  VARCHAR(100) NULL COMMENT 'KSeF number po accepted',
    `ksef_upo_path`          VARCHAR(500) NULL COMMENT 'Path do UPO XML w storage',

    -- Platnosci
    `payment_status`        ENUM('unpaid','partial','paid','overpaid') NOT NULL DEFAULT 'unpaid',
    `payment_paid_amount`   DECIMAL(12,2) NOT NULL DEFAULT 0,

    -- Powiazanie z istniejaca platnoscia
    `source_payment_id`     INT UNSIGNED NULL COMMENT 'jesli faktura wystawiona dla istniejacej platnosci',
    `notes`                 TEXT NULL,
    `pdf_path`              VARCHAR(500) NULL,
    `created_by`            INT UNSIGNED NULL,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uniq_club_number` (`club_id`, `invoice_number`),
    KEY `idx_club_status`     (`club_id`, `status`),
    KEY `idx_club_issue_date` (`club_id`, `issue_date`),
    KEY `idx_buyer_member`    (`buyer_member_id`),
    KEY `idx_source_payment`  (`source_payment_id`),

    CONSTRAINT `fk_club_invoices_club`
        FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_club_invoices_member`
        FOREIGN KEY (`buyer_member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `club_invoice_items` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id`      INT UNSIGNED NOT NULL,
    `position`        INT NOT NULL,
    `description`     VARCHAR(500) NOT NULL,
    `quantity`        DECIMAL(10,3) NOT NULL DEFAULT 1,
    `unit`            VARCHAR(20) NOT NULL DEFAULT 'szt.',
    `unit_price_net`  DECIMAL(12,2) NOT NULL,
    `vat_rate`        DECIMAL(4,2) NOT NULL DEFAULT 23.00 COMMENT 'standard: 23,8,5,0; ZW=-1; NP=-2',
    `net_amount`      DECIMAL(12,2) NOT NULL,
    `vat_amount`      DECIMAL(12,2) NOT NULL,
    `gross_amount`    DECIMAL(12,2) NOT NULL,
    `pkwiu`           VARCHAR(20) NULL,
    `gtu_code`        VARCHAR(5)  NULL COMMENT 'GTU_01..GTU_13 dla JPK',

    KEY `idx_invoice_position` (`invoice_id`, `position`),
    CONSTRAINT `fk_invoice_items_invoice`
        FOREIGN KEY (`invoice_id`) REFERENCES `club_invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `club_invoice_numbering` (
    `club_id`        INT UNSIGNED NOT NULL,
    `year`           INT NOT NULL,
    `format`         VARCHAR(50) NOT NULL DEFAULT 'FV/{seq}/{year}'
                     COMMENT 'placeholdery: {seq},{year},{month}',
    `next_sequence`  INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`club_id`, `year`),
    CONSTRAINT `fk_invoice_numbering_club`
        FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
