-- Migration 091: Publiczna strona live scoring turnieju (bez logowania)
--
-- Dodaje opt-in flagi do tournaments + log wyswietlen anonimizowany (SHA-256 IP).
-- Slug jest globalnie unikalny (cross-tenant) zeby URL /live/{slug} dzialal
-- bez kontekstu klubu. public_live_enabled musi byc rownież 1 (defense-in-depth
-- na poziomie kontrolera) zeby strona byla dostepna publicznie.
--
-- Wzorzec wzieto z migracji 080 (public_profiles): opt-in + audit views.

SET foreign_key_checks = 0;

ALTER TABLE `tournaments`
    ADD COLUMN IF NOT EXISTS `public_live_enabled` TINYINT(1)  NOT NULL DEFAULT 0
        COMMENT 'Czy publiczna strona live wynikow jest wlaczona (opt-in)'
        AFTER `status`,
    ADD COLUMN IF NOT EXISTS `public_live_slug`    VARCHAR(80) NULL
        COMMENT 'Globalnie unikalny slug w URL /live/{slug}'
        AFTER `public_live_enabled`,
    ADD COLUMN IF NOT EXISTS `public_live_full_names` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Pokazuj pelne nazwiska zamiast inicjalow (klub musi opt-in)'
        AFTER `public_live_slug`;

-- Globalna unikalnosc slug (nie per-club!)
ALTER TABLE `tournaments`
    ADD UNIQUE KEY `uniq_public_live_slug` (`public_live_slug`);

-- Log wyswietlen (anonimizowany — SHA-256 hash IP zamiast plain IP).
CREATE TABLE IF NOT EXISTS `tournament_live_views` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tournament_id` INT UNSIGNED NOT NULL,
    `ip_hash`       CHAR(64)     NOT NULL COMMENT 'SHA-256 IP (privacy by design)',
    `user_agent`    VARCHAR(255) NULL,
    `referer`       VARCHAR(255) NULL,
    `viewed_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_tournament_date` (`tournament_id`, `viewed_at`),
    KEY `idx_ip_date` (`ip_hash`, `viewed_at`),
    FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
