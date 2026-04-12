-- Migration 020: Admin overrides for club subscriptions
SET foreign_key_checks = 0;

ALTER TABLE `club_subscriptions`
  ADD COLUMN `max_members_override` INT UNSIGNED NULL,
  ADD COLUMN `max_sports_override` TINYINT UNSIGNED NULL,
  ADD COLUMN `custom_features` JSON NULL,
  ADD COLUMN `admin_notes` TEXT NULL;

SET foreign_key_checks = 1;
