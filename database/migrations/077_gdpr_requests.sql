-- ============================================================
-- Migracja 077_gdpr_requests.sql
--
-- Self-service GDPR portal: right-to-forget (art. 17 RODO)
-- + data export (art. 20 RODO).
--
-- Flow:
--   1. Czlonek sklada prosbe -> insert do `gdpr_requests` z confirmation_token
--   2. Email z linkiem potwierdzajacym (2-step verification)
--   3. Po kliknieciu linku -> status=in_progress
--   4. Worker / admin przetwarza prosbe (anonimizacja LUB generacja eksportu)
--   5. status=completed + email notification
--
-- Bezpieczenstwo:
--   - confirmation_token: 64-char hex, expires po 24h
--   - IP + user_agent rejestrowane
--   - Audit log w tenant_access_log z severity=critical przy delete
-- ============================================================

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `gdpr_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id` INT UNSIGNED NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `request_type` ENUM('export', 'delete', 'rectify', 'restrict_processing', 'object', 'portability') NOT NULL,
    `status` ENUM('pending', 'in_progress', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
    `reason` VARCHAR(500) NULL,
    `notes` TEXT NULL COMMENT 'Notatki admina / odpowiedz dla czlonka',
    `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `confirmed_at` DATETIME NULL COMMENT 'Po kliknieciu linku w emailu',
    `confirmation_token` CHAR(64) NULL,
    `confirmation_token_expires_at` DATETIME NULL,
    `processed_at` DATETIME NULL,
    `processed_by` INT UNSIGNED NULL COMMENT 'admin user_id (NULL gdy worker)',
    `export_file_path` VARCHAR(500) NULL,
    `export_file_expires_at` DATETIME NULL COMMENT 'Auto-delete po 7 dniach',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    KEY `idx_gdpr_member_status` (`member_id`, `status`),
    KEY `idx_gdpr_club_status` (`club_id`, `status`),
    KEY `idx_gdpr_token` (`confirmation_token`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Self-service GDPR requests (export, delete, rectify) z portalu czlonka';

-- Dodanie kolumny is_anonymized (anonymized_at juz istnieje od migracji 009)
ALTER TABLE `members`
    ADD COLUMN IF NOT EXISTS `is_anonymized` TINYINT(1) NOT NULL DEFAULT 0 AFTER `anonymized_at`;

-- Seedy szablonow email do email_event_catalog (PR #142)
-- Bezpieczne: ignore jesli tabela nie istnieje (migracja stand-alone)
INSERT IGNORE INTO `email_event_catalog` (`code`, `name`, `description`, `category`, `default_subject`, `default_body`, `sort_order`)
SELECT * FROM (
    SELECT 'gdpr_request_confirmation' AS `code`,
           'GDPR — potwierdzenie prosby' AS `name`,
           'Link confirmation wysylany po zlozeniu prosby GDPR (delete / export).' AS `description`,
           'gdpr' AS `category`,
           'Potwierdz prosbe GDPR' AS `default_subject`,
           'Czesc {first_name},\n\nOtrzymalismy Twoja prosbe GDPR ({request_type}) w klubie {club_name}.\nAby ja potwierdzic, kliknij ponizszy link (wazny przez 24h):\n\n{confirmation_link}\n\nJesli to nie Ty zlozyles te prosbe, zignoruj te wiadomosc.\n\nPozdrawiamy,\nKlub {club_name}' AS `default_body`,
           200 AS `sort_order`
    UNION ALL
    SELECT 'gdpr_export_ready', 'GDPR — eksport danych gotowy',
           'Powiadomienie ze plik ZIP z eksportem danych jest gotowy do pobrania.',
           'gdpr',
           'Twoj eksport danych jest gotowy',
           'Czesc {first_name},\n\nTwoj eksport danych zostal wygenerowany. Mozesz go pobrac w portalu w sekcji "Moje dane" -> "Historia prosb GDPR".\nLink wygasa za 7 dni.\n\nPozdrawiamy,\nKlub {club_name}',
           210
    UNION ALL
    SELECT 'gdpr_delete_confirmed', 'GDPR — konto usuniete',
           'Powiadomienie ze konto zostalo zanonimizowane / usuniete.',
           'gdpr',
           'Twoja prosba o usuniecie danych zostala zrealizowana',
           'Czesc,\n\nTwoja prosba o usuniecie danych w klubie {club_name} zostala zrealizowana. Twoje dane osobowe zostaly zanonimizowane zgodnie z art. 17 RODO.\n\nDziekujemy za przynaleznosc do klubu.',
           220
) tmp
WHERE EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'email_event_catalog'
);

SET foreign_key_checks = 1;
