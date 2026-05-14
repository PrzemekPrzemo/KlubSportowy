-- Sambo combat-sports base migration
-- Wspolne tabele dla sportow walki: kategorie wagowe, stopnie, historia stopni czlonkow.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `sambo_weight_classes` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `code`           VARCHAR(40) NOT NULL,
    `name`           VARCHAR(80) NOT NULL,
    `weight_min_kg`  DECIMAL(5,2) NOT NULL,
    `weight_max_kg`  DECIMAL(5,2) NOT NULL,
    `gender`         ENUM('M','F','any') NOT NULL DEFAULT 'any',
    `age_category`   VARCHAR(40) NULL,
    UNIQUE KEY `uniq_club_code` (`club_id`, `code`),
    KEY `idx_sambo_wc_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sambo_belts` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`     INT UNSIGNED NOT NULL,
    `code`        VARCHAR(40) NOT NULL,
    `name`        VARCHAR(80) NOT NULL,
    `color`       VARCHAR(20) NULL,
    `rank_order`  SMALLINT NOT NULL DEFAULT 0 COMMENT 'porzadek od najnizszego',
    UNIQUE KEY `uniq_club_code` (`club_id`, `code`),
    KEY `idx_sambo_belts_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sambo_member_grades` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`      INT UNSIGNED NOT NULL,
    `member_id`    INT UNSIGNED NOT NULL,
    `belt_id`      INT UNSIGNED NOT NULL,
    `achieved_at`  DATE NOT NULL,
    `notes`        VARCHAR(255) NULL,
    KEY `idx_sambo_grades_member` (`member_id`),
    KEY `idx_sambo_grades_club`   (`club_id`),
    KEY `idx_sambo_grades_belt`   (`belt_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)         ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)       ON DELETE CASCADE,
    FOREIGN KEY (`belt_id`)   REFERENCES `sambo_belts`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
