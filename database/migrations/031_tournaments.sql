CREATE TABLE IF NOT EXISTS `tournaments` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `sport_key`   VARCHAR(40) NOT NULL,
  `name`        VARCHAR(150) NOT NULL,
  `format`      ENUM('single_elimination','double_elimination','round_robin') NOT NULL DEFAULT 'single_elimination',
  `date_start`  DATE NOT NULL,
  `status`      ENUM('draft','active','finished') NOT NULL DEFAULT 'draft',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tour_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tournament_participants` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tournament_id`   INT UNSIGNED NOT NULL,
  `member_id`       INT UNSIGNED NOT NULL,
  `seed`            TINYINT UNSIGNED NULL,
  `eliminated`      TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY `uq_tour_member` (`tournament_id`,`member_id`),
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tournament_matches` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tournament_id`   INT UNSIGNED NOT NULL,
  `round`           TINYINT UNSIGNED NOT NULL,
  `match_number`    TINYINT UNSIGNED NOT NULL,
  `player1_id`      INT UNSIGNED NULL,
  `player2_id`      INT UNSIGNED NULL,
  `winner_id`       INT UNSIGNED NULL,
  `score1`          VARCHAR(20) NULL,
  `score2`          VARCHAR(20) NULL,
  `scheduled_at`    DATETIME NULL,
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`player1_id`)    REFERENCES `members`(`id`)     ON DELETE SET NULL,
  FOREIGN KEY (`player2_id`)    REFERENCES `members`(`id`)     ON DELETE SET NULL,
  FOREIGN KEY (`winner_id`)     REFERENCES `members`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
