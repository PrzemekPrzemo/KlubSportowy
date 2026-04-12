-- Volleyball plugin migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `volleyball_teams` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `name`            VARCHAR(120) NOT NULL,
  `league`          VARCHAR(100) NULL,
  `age_category_id` INT UNSIGNED NULL,
  `coach_id`        INT UNSIGNED NULL,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_vt_club` (`club_id`),
  FOREIGN KEY (`club_id`)         REFERENCES `clubs`(`id`)          ON DELETE CASCADE,
  FOREIGN KEY (`age_category_id`) REFERENCES `age_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`coach_id`)        REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `volleyball_matches` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `home_team_id`   INT UNSIGNED NOT NULL,
  `away_team`      VARCHAR(120) NOT NULL COMMENT 'nazwa druzyny przeciwnej (moze byc spoza systemu)',
  `away_team_id`   INT UNSIGNED NULL COMMENT 'jesli druzyna jest w systemie',
  `match_date`     DATETIME NOT NULL,
  `location`       VARCHAR(150) NULL,
  `set1_home`      TINYINT UNSIGNED NULL,
  `set1_away`      TINYINT UNSIGNED NULL,
  `set2_home`      TINYINT UNSIGNED NULL,
  `set2_away`      TINYINT UNSIGNED NULL,
  `set3_home`      TINYINT UNSIGNED NULL,
  `set3_away`      TINYINT UNSIGNED NULL,
  `set4_home`      TINYINT UNSIGNED NULL,
  `set4_away`      TINYINT UNSIGNED NULL,
  `set5_home`      TINYINT UNSIGNED NULL,
  `set5_away`      TINYINT UNSIGNED NULL,
  `home_sets`      TINYINT UNSIGNED NULL COMMENT 'total sets won by home',
  `away_sets`      TINYINT UNSIGNED NULL COMMENT 'total sets won by away',
  `home_score`     SMALLINT UNSIGNED NULL COMMENT 'total points scored by home',
  `away_score`     SMALLINT UNSIGNED NULL COMMENT 'total points scored by away',
  `referee`        VARCHAR(120) NULL,
  `match_type`     ENUM('ligowy','pucharowy','towarzyski','turniejowy') NOT NULL DEFAULT 'ligowy',
  `status`         ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') NOT NULL DEFAULT 'zaplanowany',
  `notes`          TEXT NULL,
  `created_by`     INT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_vm_club` (`club_id`),
  KEY `idx_vm_date` (`match_date`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)            ON DELETE CASCADE,
  FOREIGN KEY (`home_team_id`)  REFERENCES `volleyball_teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`away_team_id`)  REFERENCES `volleyball_teams`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `volleyball_player_stats` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id`    INT UNSIGNED NOT NULL,
  `member_id`   INT UNSIGNED NOT NULL,
  `attacks`     SMALLINT UNSIGNED NULL,
  `kills`       SMALLINT UNSIGNED NULL,
  `blocks`      SMALLINT UNSIGNED NULL,
  `serves`      SMALLINT UNSIGNED NULL,
  `aces`        SMALLINT UNSIGNED NULL,
  `digs`        SMALLINT UNSIGNED NULL,
  `errors`      SMALLINT UNSIGNED NULL,
  `sets_played` TINYINT UNSIGNED NULL,
  UNIQUE KEY `uq_vps_match_member` (`match_id`, `member_id`),
  FOREIGN KEY (`match_id`)  REFERENCES `volleyball_matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)            ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
