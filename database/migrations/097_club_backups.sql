-- ============================================================
-- Migracja 097_club_backups.sql
--
-- Tabela meta-danych pelnych backupow klubu (eksport / import ZIP).
--
-- W przeciwienstwie do `cli/backup_club.php` (mysqldump per-tabela)
-- ten zapis trzyma backupy wykonywane przez ClubExporter -> ZIP zawiera
-- manifest + data/{table}.json + media/ + integrations/.
--
-- type:
--   * manual     — zarzad klikna "Utworz backup"
--   * scheduled  — okresowy (cron / scheduled job)
--   * pre_delete — automatyczny snapshot przed twardym usunieciem klubu
--
-- status: in_progress | completed | failed (worker CLI process_club_backups.php).
-- expires_at: po tym terminie cleanup_expired_backups.php usuwa plik z dysku
-- (default = NOW + 30 dni) — zgodnie z GDPR data minimisation.
--
-- Wzorzec: jak gdpr_requests (077) + ksef_send_queue (090) — async job z
-- workerem CLI i wpisem do tenant_access_log o severity=critical.
-- ============================================================

CREATE TABLE IF NOT EXISTS `club_backups` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `club_id`            INT UNSIGNED NOT NULL,
    `type`               ENUM('manual','scheduled','pre_delete') NOT NULL DEFAULT 'manual',
    `backup_path`        VARCHAR(500) NULL
        COMMENT 'Sciezka wzgledna od ROOT_PATH (np. storage/backups/12/45_20260517_120000.zip)',
    `backup_size_bytes`  BIGINT NULL,
    `rows_exported`      INT NULL,
    `files_exported`     INT NULL,
    `status`             ENUM('in_progress','completed','failed') NOT NULL DEFAULT 'in_progress',
    `started_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at`       DATETIME NULL,
    `expires_at`         DATETIME NULL
        COMMENT 'Auto-delete pliku po tej dacie (domyslnie NOW + 30 dni).',
    `error_message`      TEXT NULL,
    `created_by_user_id` INT UNSIGNED NULL,
    PRIMARY KEY (`id`),
    KEY `idx_club_backups_status`  (`club_id`, `status`),
    KEY `idx_club_backups_expires` (`expires_at`),
    CONSTRAINT `fk_club_backups_club`
        FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
