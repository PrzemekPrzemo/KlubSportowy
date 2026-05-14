-- ============================================================
-- 062_schema_migrations.sql
-- Tabela trackingu migracji dla cli/update.php
-- ============================================================

CREATE TABLE IF NOT EXISTS `schema_migrations` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration_file VARCHAR(255) NOT NULL UNIQUE COMMENT 'np. 055_inpost_shipping.sql lub Sports/Football/001_football.sql',
    checksum VARCHAR(64) NULL COMMENT 'SHA-256 zawartości pliku w momencie aplikacji',
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    duration_ms INT UNSIGNED NULL,
    status ENUM('success','partial','failed') NOT NULL DEFAULT 'success',
    error_message TEXT NULL,
    KEY idx_applied_at (applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
