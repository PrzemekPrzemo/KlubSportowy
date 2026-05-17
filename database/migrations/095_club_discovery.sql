-- Migration 095: Publiczny katalog klubow (Club Discovery) + lead-gen
--
-- Dodaje opt-in flagi do `clubs` (rodzic moze znalezc klub przez ClubDesk)
-- oraz log wyswietlen anonimizowany (SHA-256 IP). Slug klubu jest globalnie
-- unikalny (cross-tenant) zeby URL /discover/club/{slug} dzialal bez kontekstu klubu.
--
-- Wzorzec wziety z migracji 091 (live_scoring_public) i 080 (public_profiles):
-- opt-in + audit views + globalnie unikalny slug.
--
-- UWAGA: tabela `clubs` nie ma kolumny `status` (ma `is_active`). Wszystkie
-- nowe kolumny dodawane sa po `is_active` lub `address` (a nie po `status`).

SET foreign_key_checks = 0;

ALTER TABLE `clubs`
    ADD COLUMN IF NOT EXISTS `public_discovery_enabled` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Czy publiczna prezentacja klubu w katalogu /discover jest wlaczona (opt-in)'
        AFTER `is_active`,
    ADD COLUMN IF NOT EXISTS `public_slug` VARCHAR(80) NULL
        COMMENT 'Globalnie unikalny slug w URL /discover/club/{slug}'
        AFTER `public_discovery_enabled`,
    ADD COLUMN IF NOT EXISTS `latitude` DECIMAL(10,6) NULL
        COMMENT 'Geolokalizacja: szerokosc (z geocodera Nominatim)'
        AFTER `address`,
    ADD COLUMN IF NOT EXISTS `longitude` DECIMAL(10,6) NULL
        COMMENT 'Geolokalizacja: dlugosc'
        AFTER `latitude`,
    ADD COLUMN IF NOT EXISTS `sports_offered_json` JSON NULL
        COMMENT 'Lista sportow oferowanych (cache z club_sports dla szybkiej listy publicznej)'
        AFTER `longitude`,
    ADD COLUMN IF NOT EXISTS `description_short` VARCHAR(500) NULL
        COMMENT 'Krotki opis klubu (max 500 znakow) — pokazywany na karcie i landing page'
        AFTER `sports_offered_json`,
    ADD COLUMN IF NOT EXISTS `contact_phone` VARCHAR(50) NULL
        COMMENT 'Publiczny telefon kontaktowy (moze byc inny niz `phone`)'
        AFTER `description_short`,
    ADD COLUMN IF NOT EXISTS `website_url` VARCHAR(255) NULL
        COMMENT 'Publiczny URL strony klubu (rozszerza istniejacy `website`)'
        AFTER `contact_phone`;

-- Index per discovery (filter + map bbox queries)
ALTER TABLE `clubs`
    ADD INDEX `idx_discovery` (`public_discovery_enabled`, `latitude`, `longitude`);

-- Unikalny slug globalnie (nie per-club!)
ALTER TABLE `clubs`
    ADD UNIQUE KEY `uniq_public_slug` (`public_slug`);

-- Log wyswietlen klubu w katalogu (anonimizowany — SHA-256 IP).
CREATE TABLE IF NOT EXISTS `club_discovery_views` (
    `id`        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`   INT UNSIGNED NOT NULL,
    `ip_hash`   CHAR(64)     NOT NULL COMMENT 'SHA-256 IP (privacy by design)',
    `source`    ENUM('discover_list','sport_filter','map','direct') NOT NULL DEFAULT 'direct',
    `viewed_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_club_date` (`club_id`, `viewed_at`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit wyswietlen klubu w publicznym katalogu /discover (privacy: hash IP)';

SET foreign_key_checks = 1;
