-- 090: KSeF Phase 3 — send queue + UPO archive.
--
-- Phase 1 (#088) zalozyl club_ksef_config + ksef_audit_log.
-- Phase 2 (#089) dodal club_invoices + numbering + FA(2) XML generator.
-- Phase 3 (THIS) dodaje warstwe wysylki:
--   * ksef_send_queue   — per-invoice queue dla XAdES sign + dispatch + UPO poll
--   * ksef_upo_archive  — przechowywane UPO XML zwrocone przez MF
--
-- Multi-tenant: kazda tabela ma club_id NOT NULL + FK CASCADE.
-- Idempotencja: UNIQUE(invoice_id) na queue + archive — jedna faktura =
-- max jeden wpis kolejkowy, max jedno UPO.
--
-- Lockowanie: zadania pobierane przez worker uzywaja SELECT ... FOR UPDATE
-- SKIP LOCKED (InnoDB 8+) zeby kilka workerow rownolegle nie zlapalo tej
-- samej pozycji.

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `ksef_send_queue` (
    `id`                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`                  INT UNSIGNED NOT NULL,
    `invoice_id`               INT UNSIGNED NOT NULL,
    `status`                   ENUM('queued','signing','sending','awaiting_upo','completed','failed','retrying')
                               NOT NULL DEFAULT 'queued',
    `attempts`                 TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `next_retry_at`            DATETIME NULL,
    -- KSeF session / reference numbers
    `ksef_session_token`       VARCHAR(255) NULL COMMENT 'Live session token — czyszczony po completed/failed',
    `ksef_reference`           VARCHAR(100) NULL COMMENT 'referenceNumber zwrocony przez Invoice/Send',
    `ksef_element_reference`   VARCHAR(100) NULL COMMENT 'elementReferenceNumber zwrocony przez Invoice/Send',
    -- Bledy
    `last_error_code`          VARCHAR(50)  NULL,
    `last_error_message`       TEXT         NULL,
    -- Audit
    `queued_at`                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `signed_at`                DATETIME NULL,
    `sent_at`                  DATETIME NULL,
    `upo_received_at`          DATETIME NULL,
    `updated_at`               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY `idx_status_retry`     (`status`, `next_retry_at`),
    KEY `idx_club_invoice`     (`club_id`, `invoice_id`),
    UNIQUE KEY `uniq_invoice`  (`invoice_id`),

    CONSTRAINT `fk_ksef_queue_club`
        FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)         ON DELETE CASCADE,
    CONSTRAINT `fk_ksef_queue_invoice`
        FOREIGN KEY (`invoice_id`) REFERENCES `club_invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `ksef_upo_archive` (
    `id`                       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id`               INT UNSIGNED NOT NULL,
    `club_id`                  INT UNSIGNED NOT NULL,
    `upo_xml_path`             VARCHAR(500) NOT NULL,
    `ksef_reference`           VARCHAR(100) NOT NULL,
    `acquisition_timestamp`    DATETIME     NOT NULL COMMENT 'Czas potwierdzenia z UPO',
    `document_hash`            VARCHAR(128) NOT NULL COMMENT 'SHA-256 oryginalnego XML faktury',
    `created_at`               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `uniq_invoice_upo` (`invoice_id`),
    KEY `idx_club`                (`club_id`),

    CONSTRAINT `fk_ksef_upo_club`
        FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)         ON DELETE CASCADE,
    CONSTRAINT `fk_ksef_upo_invoice`
        FOREIGN KEY (`invoice_id`) REFERENCES `club_invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Rozszerzenie audit log o akcje wysylkowe (Phase 3). MySQL ENUM musi byc
-- nadpisany razem z poprzednimi wartosciami (#088).
ALTER TABLE `ksef_audit_log`
    MODIFY COLUMN `action` ENUM(
        'config_change','enabled','disabled','connection_test',
        'token_set','cert_uploaded',
        -- Phase 3
        'queue_enqueued','queue_signed','queue_sent','queue_completed',
        'queue_failed','queue_retry','queue_force_retry','queue_force_fail',
        'upo_archived'
    ) NOT NULL;

SET foreign_key_checks = 1;
