-- Athletics plugin migration — competitions
-- Pozwala grupować wyniki z athletics_records w wydarzenia (zawody),
-- śledzić daty, lokalizacje i typ zawodów (klubowe / regionalne / krajowe / mistrzostwa).
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `athletics_competitions` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `name`       VARCHAR(200) NOT NULL,
  `location`   VARCHAR(150) NULL,
  `date_from`  DATE NOT NULL,
  `date_to`    DATE NULL,
  `type`       ENUM('klubowe','regionalne','krajowe','mistrzostwa','miting','inne') NOT NULL DEFAULT 'klubowe',
  `status`     ENUM('zaplanowane','w_trakcie','zakonczone','odwolane') NOT NULL DEFAULT 'zaplanowane',
  `notes`      TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_acomp_club` (`club_id`),
  KEY `idx_acomp_date` (`date_from`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `athletics_records`
  ADD COLUMN `competition_id` INT UNSIGNED NULL AFTER `discipline_id`,
  ADD KEY `idx_ar_competition` (`competition_id`),
  ADD CONSTRAINT `fk_ar_competition`
    FOREIGN KEY (`competition_id`) REFERENCES `athletics_competitions`(`id`) ON DELETE SET NULL;

SET foreign_key_checks = 1;
