-- ============================================================
-- Migracja 066_tenant_access_log.sql
--
-- Tabela auditu cross-tenant data access.
--
-- Rejestruje kazde wywolanie ClubScopedModel::withoutScope() — pozwala
-- super-adminowi przegladac kto, kiedy i z jakiej tabeli czytal/pisal
-- DANE BEZ FILTRA club_id (czyli potencjalnie wszystkich tenantow).
--
-- Wzorzec inspirowany Hovera (per-tenant DB grants) zaadaptowany do
-- nasze go shared-schema modelu — defense-in-depth, nie zastepuje
-- ClubScopedModel ale dodaje obserwowalnosc.
--
-- Wpisy mozna przegladac w /admin/audit/access-log.
-- Cron czysci wpisy starsze niz 90 dni (rotacja jak w sensitive_access_log).
-- ============================================================

CREATE TABLE IF NOT EXISTS `tenant_access_log` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `occurred_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_id`           INT UNSIGNED NULL,
    `username`          VARCHAR(190) NULL,
    `is_super_admin`    TINYINT(1) NOT NULL DEFAULT 0,
    `active_club_id`    INT UNSIGNED NULL,
    `table_name`        VARCHAR(190) NOT NULL,
    `operation`         ENUM('read','write','delete','count') NOT NULL DEFAULT 'read',
    `caller_file`       VARCHAR(255) NULL,
    `caller_line`       INT UNSIGNED NULL,
    `caller_class`      VARCHAR(190) NULL,
    `request_path`      VARCHAR(255) NULL,
    `request_method`    VARCHAR(10) NULL,
    `severity`          ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
    `notes`             VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_tal_occurred_at` (`occurred_at`),
    KEY `idx_tal_user_id`     (`user_id`),
    KEY `idx_tal_table`       (`table_name`),
    KEY `idx_tal_severity`    (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
