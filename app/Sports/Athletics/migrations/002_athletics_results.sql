-- Athletics: competition results table (separate from personal records)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `athletics_results` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`          INT UNSIGNED NOT NULL,
  `member_id`        INT UNSIGNED NOT NULL,
  `discipline_name`  VARCHAR(80)  NOT NULL COMMENT 'np. 100m, skok wzwyż, pchnięcie kulą',
  `result_value`     DECIMAL(10,3) NOT NULL,
  `result_unit`      ENUM('s','min','m','cm','kg','pts') NOT NULL DEFAULT 's',
  `wind_ms`          DECIMAL(4,1) NULL COMMENT 'prędkość wiatru m/s',
  `competition_name` VARCHAR(200) NOT NULL,
  `competition_date` DATE NOT NULL,
  `placement`        TINYINT UNSIGNED NULL,
  `age_category`     VARCHAR(60) NULL,
  `location`         VARCHAR(150) NULL,
  `notes`            TEXT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ares_club`       (`club_id`),
  KEY `idx_ares_member`     (`member_id`),
  KEY `idx_ares_discipline` (`discipline_name`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wyniki zawodów lekkoatletycznych';

SET foreign_key_checks = 1;
