-- ============================================================
-- Migracja 050_branding_extra_logos.sql
--
-- Faza W.2 — rozszerzenie systemu logo:
--   - club_customization: dodaje 2 dodatkowe sloty logo (alt + dark)
--   - club_sports:        dodaje 3 sloty logo per sport per klub
--
-- Logika dokumentów (PDF/raporty):
--   1. Logo systemu (Master Admin)
--   2. Logo klubu (główne + opcjonalne warianty)
--   3. Logo sekcji sportowej (opcjonalne — np. logo drużyny piłkarskiej)
--
-- Migracja additive: istniejący logo_path zostaje. Wszystkie nowe
-- kolumny NULL = brak (fallback na poziom wyżej).
-- ============================================================

SET foreign_key_checks = 0;

-- club_customization: warianty alt + dark dla logo klubu
ALTER TABLE `club_customization`
  ADD COLUMN `logo_alt_path`  VARCHAR(255) NULL AFTER `logo_path`,
  ADD COLUMN `logo_dark_path` VARCHAR(255) NULL AFTER `logo_alt_path`;

-- club_sports: 3 sloty logo per sekcja sportowa
ALTER TABLE `club_sports`
  ADD COLUMN `logo_main_path` VARCHAR(255) NULL AFTER `name`,
  ADD COLUMN `logo_alt_path`  VARCHAR(255) NULL AFTER `logo_main_path`,
  ADD COLUMN `logo_dark_path` VARCHAR(255) NULL AFTER `logo_alt_path`;

SET foreign_key_checks = 1;
