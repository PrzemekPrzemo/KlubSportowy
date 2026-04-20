-- Judo module migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `judo_belts` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`      INT UNSIGNED NOT NULL,
  `member_id`    INT UNSIGNED NOT NULL,
  `belt_level`   ENUM(
    '6kyu','5kyu','4kyu','3kyu','2kyu','1kyu',
    '1dan','2dan','3dan','4dan','5dan','6dan','7dan','8dan'
  ) NOT NULL DEFAULT '6kyu',
  `granted_date` DATE NOT NULL,
  `examiner`     VARCHAR(100) NULL,
  `location`     VARCHAR(150) NULL,
  `notes`        TEXT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_jb_club`   (`club_id`),
  KEY `idx_jb_member` (`member_id`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `judo_results` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`          INT UNSIGNED NOT NULL,
  `member_id`        INT UNSIGNED NOT NULL,
  `competition_name` VARCHAR(200) NOT NULL,
  `competition_date` DATE NOT NULL,
  `weight_class`     ENUM('-46','-50','-55','-60','-66','-73','-81','-90','-100','+100','open') NULL,
  `age_category`     VARCHAR(50) NULL COMMENT 'np. U18, Junior, Senior',
  `placement`        TINYINT UNSIGNED NULL COMMENT '1=zloto, 2=srebro, 3=brazowy',
  `category`         ENUM('walka','kata','para_judo') NOT NULL DEFAULT 'walka',
  `notes`            TEXT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_jr_club`   (`club_id`),
  KEY `idx_jr_member` (`member_id`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
