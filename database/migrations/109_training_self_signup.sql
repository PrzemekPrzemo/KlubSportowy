-- ============================================================
-- Migracja 109: member training self-signup + waitlist
--
-- Cel: pozwolic czlonkowi samodzielnie zapisac/wypisac sie
-- na trening z portalu zawodnika; opcjonalna waitlist + deadline.
--
-- Zachowuje wsteczna kompatybilnosc z istniejacym ENUM'em
-- (zapisany / obecny / nieobecny / spozniony / wypisany) i dodaje
-- semantyczne wartosci: signed_up / waitlist / attended /
-- absent / cancelled (uzywane przez nowy flow).
-- ============================================================

SET foreign_key_checks = 0;

-- Trenings: ustawienia self-signup. max_participants juz istnieje (001_trainings.sql),
-- wiec NIE dodajemy ponownie.
ALTER TABLE `trainings`
  ADD COLUMN IF NOT EXISTS `signup_enabled`        TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `signup_deadline_hours` INT NOT NULL DEFAULT 2
      COMMENT 'minimalny odstep w godzinach przed startem; po nim brak (wy)pisywania',
  ADD COLUMN IF NOT EXISTS `waitlist_enabled`      TINYINT(1) NOT NULL DEFAULT 1;

-- Status ENUM dla training_attendees — rozszerzamy o nowe wartosci.
-- Zachowujemy stare (PL) zeby istniejace rekordy/inny kod dzialal.
ALTER TABLE `training_attendees`
  MODIFY COLUMN `status` ENUM(
      'zapisany','obecny','nieobecny','spozniony','wypisany',
      'signed_up','waitlist','attended','absent','cancelled'
  ) NOT NULL DEFAULT 'signed_up';

-- Metadane self-signup: zrodlo + audyt.
ALTER TABLE `training_attendees`
  ADD COLUMN IF NOT EXISTS `signup_source` ENUM('admin','trainer','member_self','recurring')
       NOT NULL DEFAULT 'admin' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `signed_up_at`        DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `cancelled_at`        DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `cancellation_reason` VARCHAR(500) NULL;

-- Index do szybkiego countu w transakcji (FOR UPDATE) per trening+status.
-- MySQL/MariaDB ignoruje DUPLICATE KEY przy ADD INDEX IF NOT EXISTS.
ALTER TABLE `training_attendees`
  ADD INDEX IF NOT EXISTS `idx_training_status` (`training_id`, `status`);

SET foreign_key_checks = 1;
