-- 070: Rozszerzenie fee_rates o kategorie wiekowe, rabaty i auto-assign.
-- Repo nie ma tabeli `fees_definitions` — odpowiednikiem jest `fee_rates`.
-- Uzywamy MariaDB-native `ADD COLUMN IF NOT EXISTS` (idempotentne, dziala
-- przez PDO bez DELIMITER).

SET foreign_key_checks = 0;

ALTER TABLE `fee_rates`
    ADD COLUMN IF NOT EXISTS `age_category` VARCHAR(40) NULL COMMENT 'np. junior/senior/dziecko',
    ADD COLUMN IF NOT EXISTS `min_age_years` TINYINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `max_age_years` TINYINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `discount_pct` DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT 'Procent rabatu',
    ADD COLUMN IF NOT EXISTS `auto_assign_for_new_members` TINYINT(1) NOT NULL DEFAULT 0;

SET foreign_key_checks = 1;
