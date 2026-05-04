-- Football leagues migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `football_leagues` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `name`       VARCHAR(120) NOT NULL,
  `season`     VARCHAR(10) NOT NULL,
  `start_date` DATE NULL,
  `end_date`   DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_fl_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `football_league_teams` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `league_id`      INT UNSIGNED NOT NULL,
  `team_id`        INT UNSIGNED NOT NULL,
  `points`         INT NOT NULL DEFAULT 0,
  `games_played`   INT NOT NULL DEFAULT 0,
  `wins`           INT NOT NULL DEFAULT 0,
  `draws`          INT NOT NULL DEFAULT 0,
  `losses`         INT NOT NULL DEFAULT 0,
  `goals_for`      INT NOT NULL DEFAULT 0,
  `goals_against`  INT NOT NULL DEFAULT 0,
  `goal_diff`      INT GENERATED ALWAYS AS (`goals_for` - `goals_against`) STORED,
  UNIQUE KEY `uq_league_team` (`league_id`, `team_id`),
  KEY `idx_flt_league` (`league_id`),
  KEY `idx_flt_team` (`team_id`),
  FOREIGN KEY (`league_id`) REFERENCES `football_leagues`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team_id`)   REFERENCES `football_teams`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
