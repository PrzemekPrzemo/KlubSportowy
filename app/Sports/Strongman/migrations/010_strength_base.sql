-- Strongman strength-sports base migration
-- Wzorzec: app/Sports/Weightlifting/migrations/010_strength_base.sql
-- Kategorie wagowe, proby (martwy ciag, podnoszenie kamienia atlasa,
-- log lift, farmer walk, ...) i rekordy zyciowe.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `strongman_weight_classes` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `code`           VARCHAR(40) NOT NULL,
    `name`           VARCHAR(80) NOT NULL,
    `weight_max_kg`  DECIMAL(5,2) NOT NULL,
    `gender`         ENUM('M','F','any') NOT NULL DEFAULT 'any',
    UNIQUE KEY `uniq_club_code` (`club_id`, `code`),
    KEY `idx_strongman_wc_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `strongman_attempts` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NOT NULL,
    `member_id`       INT UNSIGNED NOT NULL,
    `event_id`        INT UNSIGNED NULL,
    `discipline`      VARCHAR(40) NOT NULL COMMENT 'deadlift/atlas_stones/log_lift/farmer_walk/yoke/truck_pull/...',
    `attempt_number`  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `weight_kg`       DECIMAL(6,2) NULL,
    `reps_completed`  INT UNSIGNED NULL,
    `time_seconds`    INT UNSIGNED NULL COMMENT 'dla walk/pull/medley',
    `distance_m`      DECIMAL(6,2) NULL COMMENT 'dla farmer walk/yoke',
    `successful`      TINYINT(1) NOT NULL DEFAULT 0,
    `notes`           VARCHAR(255) NULL,
    `attempted_at`    DATETIME NOT NULL,
    KEY `idx_strongman_att_member_disc` (`member_id`, `discipline`),
    KEY `idx_strongman_att_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `strongman_personal_records` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NOT NULL,
    `member_id`       INT UNSIGNED NOT NULL,
    `discipline`      VARCHAR(40) NOT NULL,
    `weight_kg`       DECIMAL(6,2) NULL,
    `reps`            INT UNSIGNED NULL,
    `time_seconds`    INT UNSIGNED NULL,
    `achieved_at`     DATE NOT NULL,
    `is_competition`  TINYINT(1) NOT NULL DEFAULT 0,
    `notes`           VARCHAR(255) NULL,
    KEY `idx_strongman_pr_member_disc` (`member_id`, `discipline`),
    KEY `idx_strongman_pr_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
