-- Athletics plugin migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `athletics_records` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`          INT UNSIGNED NOT NULL,
  `member_id`        INT UNSIGNED NOT NULL,
  `discipline_id`    INT UNSIGNED NULL,
  `result_value`     DECIMAL(10,3) NOT NULL COMMENT 'wynik liczbowy',
  `result_unit`      ENUM('s','min','m','cm','kg') NOT NULL DEFAULT 's',
  `record_date`      DATE NOT NULL,
  `competition_name` VARCHAR(200) NULL,
  `location`         VARCHAR(150) NULL,
  `is_personal_best` TINYINT(1) NOT NULL DEFAULT 0,
  `is_club_record`   TINYINT(1) NOT NULL DEFAULT 0,
  `notes`            TEXT NULL,
  `created_by`       INT UNSIGNED NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ar_club`   (`club_id`),
  KEY `idx_ar_member` (`member_id`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wyniki lekkoatletyczne (biegi, skoki, rzuty)';

SET foreign_key_checks = 1;
