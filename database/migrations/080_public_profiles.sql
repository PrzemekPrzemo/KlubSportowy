-- ============================================================
-- Migracja 080_public_profiles.sql
--
-- Public member profile — opt-in widget dla publicznych rankingow.
-- Czlonek decyduje czy jego osiagniecia sa publicznie widoczne
-- (URL: portal.clubdesk.pl/u/jan-kowalski-azs-warszawa).
--
-- Domyslnie wszystkie profile sa PRIVATE (opt-in only).
-- Publiczne profile pokazuja tylko niewrazliwe dane:
--   - imie / nazwisko / sport / rankingi / achievements
-- NIGDY: PESEL, adres, telefon, email.
--
-- Bezpieczenstwo:
--   - Anonimowani czlonkowie (is_anonymized=1) — auto-disable
--   - IP rate limit per profile_views (anti-scrape)
--   - Audit log w tenant_access_log gdy visibility=public
-- ============================================================

ALTER TABLE `members`
    ADD COLUMN IF NOT EXISTS `public_profile_visibility` ENUM('private','club_only','public') NOT NULL DEFAULT 'private',
    ADD COLUMN IF NOT EXISTS `public_profile_slug` VARCHAR(120) NULL COMMENT 'URL-safe: jan-kowalski-x123 unique globally',
    ADD COLUMN IF NOT EXISTS `public_profile_bio` TEXT NULL COMMENT 'Krotki opis "o mnie", max 500 znakow',
    ADD COLUMN IF NOT EXISTS `public_profile_show_avatar` TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `public_profile_show_age` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `public_profile_show_birth_year` TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `public_profile_show_sports` TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `public_profile_show_rankings` TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `public_profile_show_achievements` TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `public_profile_show_tournaments` TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `public_profile_show_club` TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `public_profile_view_count` INT UNSIGNED NOT NULL DEFAULT 0,
    ADD UNIQUE KEY IF NOT EXISTS `uniq_public_slug` (`public_profile_slug`);

-- Audit log per public profile view (anti-scrape + analytics dla wlasciciela)
CREATE TABLE IF NOT EXISTS `public_profile_views` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT UNSIGNED NOT NULL,
    `viewer_ip` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `referrer` VARCHAR(500) NULL,
    `viewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_member_date` (`member_id`, `viewed_at`),
    KEY `idx_ip_date` (`viewer_ip`, `viewed_at`),
    CONSTRAINT `fk_public_profile_views_member`
        FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
