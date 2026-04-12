-- Basketball plugin migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `basketball_teams` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `name`            VARCHAR(120) NOT NULL,
  `league`          VARCHAR(100) NULL,
  `age_category_id` INT UNSIGNED NULL,
  `coach_id`        INT UNSIGNED NULL,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_bt_club` (`club_id`),
  FOREIGN KEY (`club_id`)         REFERENCES `clubs`(`id`)          ON DELETE CASCADE,
  FOREIGN KEY (`age_category_id`) REFERENCES `age_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`coach_id`)        REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `basketball_matches` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `home_team_id`   INT UNSIGNED NOT NULL,
  `away_team`      VARCHAR(120) NOT NULL COMMENT 'nazwa druzyny przeciwnej (moze byc spoza systemu)',
  `away_team_id`   INT UNSIGNED NULL COMMENT 'jesli druzyna jest w systemie',
  `match_date`     DATETIME NOT NULL,
  `location`       VARCHAR(150) NULL,
  `q1_home`        TINYINT UNSIGNED NULL,
  `q1_away`        TINYINT UNSIGNED NULL,
  `q2_home`        TINYINT UNSIGNED NULL,
  `q2_away`        TINYINT UNSIGNED NULL,
  `q3_home`        TINYINT UNSIGNED NULL,
  `q3_away`        TINYINT UNSIGNED NULL,
  `q4_home`        TINYINT UNSIGNED NULL,
  `q4_away`        TINYINT UNSIGNED NULL,
  `overtime_home`  TINYINT UNSIGNED NULL,
  `overtime_away`  TINYINT UNSIGNED NULL,
  `home_score`     TINYINT UNSIGNED NULL,
  `away_score`     TINYINT UNSIGNED NULL,
  `referee`        VARCHAR(120) NULL,
  `match_type`     ENUM('ligowy','pucharowy','towarzyski','turniejowy') NOT NULL DEFAULT 'ligowy',
  `status`         ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') NOT NULL DEFAULT 'zaplanowany',
  `notes`          TEXT NULL,
  `created_by`     INT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_bm_club` (`club_id`),
  KEY `idx_bm_date` (`match_date`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)            ON DELETE CASCADE,
  FOREIGN KEY (`home_team_id`)  REFERENCES `basketball_teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`away_team_id`)  REFERENCES `basketball_teams`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `basketball_player_stats` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id`            INT UNSIGNED NOT NULL,
  `member_id`           INT UNSIGNED NOT NULL,
  `minutes`             SMALLINT UNSIGNED NULL,
  `points`              SMALLINT UNSIGNED NULL,
  `assists`             SMALLINT UNSIGNED NULL,
  `rebounds`            SMALLINT UNSIGNED NULL,
  `steals`              SMALLINT UNSIGNED NULL,
  `blocks`              SMALLINT UNSIGNED NULL,
  `turnovers`           SMALLINT UNSIGNED NULL,
  `fouls`               SMALLINT UNSIGNED NULL,
  `three_pointers`      SMALLINT UNSIGNED NULL,
  `free_throws_made`    SMALLINT UNSIGNED NULL,
  `free_throws_attempts` SMALLINT UNSIGNED NULL,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_bps_match_member` (`match_id`, `member_id`),
  KEY `idx_bps_match` (`match_id`),
  FOREIGN KEY (`match_id`)  REFERENCES `basketball_matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)            ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
