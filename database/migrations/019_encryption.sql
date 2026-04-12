-- Migration: encrypted columns for sensitive data
-- pesel, email, phone → TEXT (ciphertext), _hash → VARCHAR(64) for lookups

ALTER TABLE `members`
  ADD COLUMN `pesel_hash` VARCHAR(64) NULL AFTER `pesel`,
  ADD COLUMN `email_hash` VARCHAR(64) NULL AFTER `email`,
  ADD COLUMN `phone_hash` VARCHAR(64) NULL AFTER `phone`;

ALTER TABLE `members` MODIFY `pesel` TEXT NULL;
ALTER TABLE `members` MODIFY `email` TEXT NULL;
ALTER TABLE `members` MODIFY `phone` TEXT NULL;

CREATE INDEX `idx_members_pesel_hash` ON `members` (`pesel_hash`);
CREATE INDEX `idx_members_email_hash` ON `members` (`email_hash`);
CREATE INDEX `idx_members_phone_hash` ON `members` (`phone_hash`);
