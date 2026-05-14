-- ============================================================
-- Migracja 059_whitelabel.sql
--
-- Pelen whitelabel per-klub:
--   - custom_css            sanitized CSS injection do <head>
--   - favicon_path          per-klub favicon serwowany z /favicon.ico
--   - email_header_html     HTML snippet wstawiany do nagl. emaili
--   - email_from_name       display name w From: header
--   - sms_sender_id         alphanum SMS sender (1-11 znakow A-Z, 0-9)
--
-- Idempotent: kazda kolumna dodawana z ochrona przed re-run-em
-- (information_schema check). MySQL 8 obsluguje IF NOT EXISTS w
-- ALTER TABLE ADD COLUMN od 8.0.29; uzywamy starszego wzorca z
-- procedura ad-hoc dla maksymalnej kompatybilnosci.
-- ============================================================

SET foreign_key_checks = 0;

-- custom_css juz istnieje w club_customization (schema.sql) — pomijamy.
-- Dodajemy timestamp ostatniej edycji custom_css.
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'club_customization'
     AND COLUMN_NAME  = 'custom_css_updated_at'
);
SET @sql := IF(@col_exists = 0,
  "ALTER TABLE `club_customization` ADD COLUMN `custom_css_updated_at` DATETIME NULL AFTER `custom_css`",
  "SELECT 'custom_css_updated_at exists' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'club_customization'
     AND COLUMN_NAME  = 'favicon_path'
);
SET @sql := IF(@col_exists = 0,
  "ALTER TABLE `club_customization` ADD COLUMN `favicon_path` VARCHAR(255) NULL COMMENT 'Sciezka per-klub favicon (PNG/ICO)' AFTER `logo_dark_path`",
  "SELECT 'favicon_path exists' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'club_customization'
     AND COLUMN_NAME  = 'email_header_html'
);
SET @sql := IF(@col_exists = 0,
  "ALTER TABLE `club_customization` ADD COLUMN `email_header_html` TEXT NULL COMMENT 'HTML do <header> w emailach (max 5000 chars)'",
  "SELECT 'email_header_html exists' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'club_customization'
     AND COLUMN_NAME  = 'email_from_name'
);
SET @sql := IF(@col_exists = 0,
  "ALTER TABLE `club_customization` ADD COLUMN `email_from_name` VARCHAR(120) NULL COMMENT 'Display name w From: header'",
  "SELECT 'email_from_name exists' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME   = 'club_customization'
     AND COLUMN_NAME  = 'sms_sender_id'
);
SET @sql := IF(@col_exists = 0,
  "ALTER TABLE `club_customization` ADD COLUMN `sms_sender_id` VARCHAR(11) NULL COMMENT 'Alphanum sender 1-11 znakow A-Z, 0-9'",
  "SELECT 'sms_sender_id exists' AS msg"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET foreign_key_checks = 1;
