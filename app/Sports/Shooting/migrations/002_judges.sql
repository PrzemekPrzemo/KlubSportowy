-- Shooting plugin migration: judges (sędziowie PZSS)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `judge_licenses` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `member_id`      INT UNSIGNED NOT NULL,
  `class`          ENUM('III','II','I','P') NOT NULL DEFAULT 'III' COMMENT 'PZSS: III, II, I lub Państwowa',
  `license_number` VARCHAR(60) NOT NULL,
  `disciplines`    VARCHAR(255) NULL COMMENT 'CSV listy dyscyplin (PS, KS, TR...)',
  `issue_date`     DATE NOT NULL,
  `valid_until`    DATE NOT NULL,
  `status`         ENUM('aktywna','wygasla','zawieszona') NOT NULL DEFAULT 'aktywna',
  `fee_paid`       DECIMAL(10,2) NULL,
  `notes`          TEXT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_jl_club`   (`club_id`),
  KEY `idx_jl_member` (`member_id`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Licencje sędziowskie PZSS';

SET foreign_key_checks = 1;
