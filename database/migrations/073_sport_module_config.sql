-- 073_sport_module_config.sql
-- ============================================================
-- Generic CRUD scaffolding dla 40+ sportow.
--
-- Każdy moduł sportu (app/Sports/<X>) ma 1–4 tabele per-sport
-- (np. `judo_belts`, `judo_weight_classes`, `judo_member_grades`).
-- Tylko ~6 sportów (Football, Basketball, Volleyball, Shooting,
-- Rollerskating, Athletics) ma dedykowane controllery + UI.
-- Reszta ma tabele w DB, ale klub nie ma jak wpisywać danych.
--
-- Ta tabela jest whitelistą: (sport_key, resource_key, table_name)
-- którą generyczny SportModuleController używa do walidacji
-- wszystkich żądań CRUD. NIGDY user input nie trafia do nazwy
-- tabeli — wyłącznie wartości z tej tabeli.
--
-- Seed: cli/seed_sport_modules.php skanuje app/Sports/*/migrations/
-- i automatycznie INSERTuje wpisy dla wszystkich CREATE TABLE.
-- ============================================================

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `sport_module_resources` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sport_key`      VARCHAR(40)  NOT NULL COMMENT 'klucz sportu z manifest.php (np. judo, mma)',
    `resource_key`   VARCHAR(60)  NOT NULL COMMENT 'krotka nazwa zasobu (np. belts, weight_classes)',
    `resource_label` VARCHAR(120) NOT NULL COMMENT 'etykieta w UI (PL)',
    `table_name`     VARCHAR(120) NOT NULL COMMENT 'pelna nazwa tabeli w DB',
    `icon`           VARCHAR(60)  NULL DEFAULT 'bi-table' COMMENT 'Bootstrap Icons (np. bi-award)',
    `sort_order`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_sport_resource` (`sport_key`, `resource_key`),
    UNIQUE KEY `uniq_table_name`     (`table_name`),
    KEY `idx_sport_active` (`sport_key`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Whitelist tabel per-sport dla generycznego SportModuleController (auto-CRUD UI)';

SET foreign_key_checks = 1;
