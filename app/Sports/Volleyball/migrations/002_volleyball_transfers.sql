-- Volleyball plugin migration — transfers
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `volleyball_transfers` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `member_id`      INT UNSIGNED NOT NULL,
  `direction`      ENUM('przychodzacy','odchodzacy','wypozyczenie') NOT NULL,
  `from_club`      VARCHAR(150) NULL,
  `to_club`        VARCHAR(150) NULL,
  `transfer_date`  DATE NOT NULL,
  `fee`            DECIMAL(12,2) NULL,
  `contract_until` DATE NULL,
  `notes`          TEXT NULL,
  `created_by`     INT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_vtrans_club` (`club_id`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)  REFERENCES `members`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
