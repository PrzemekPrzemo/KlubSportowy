-- Football plugin migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `football_teams` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `name`            VARCHAR(120) NOT NULL,
  `age_category_id` INT UNSIGNED NULL,
  `league`          VARCHAR(100) NULL,
  `coach_id`        INT UNSIGNED NULL,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ft_club` (`club_id`),
  FOREIGN KEY (`club_id`)         REFERENCES `clubs`(`id`)          ON DELETE CASCADE,
  FOREIGN KEY (`age_category_id`) REFERENCES `age_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`coach_id`)        REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `football_matches` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `home_team_id`   INT UNSIGNED NOT NULL,
  `away_team`      VARCHAR(120) NOT NULL COMMENT 'nazwa druzyny przeciwnej (moze byc spoza systemu)',
  `away_team_id`   INT UNSIGNED NULL COMMENT 'jesli druzyna jest w systemie',
  `match_date`     DATETIME NOT NULL,
  `location`       VARCHAR(150) NULL,
  `home_score`     TINYINT UNSIGNED NULL,
  `away_score`     TINYINT UNSIGNED NULL,
  `referee`        VARCHAR(120) NULL,
  `league_round`   VARCHAR(40) NULL,
  `match_type`     ENUM('ligowy','pucharowy','towarzyski','turniejowy') NOT NULL DEFAULT 'ligowy',
  `status`         ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') NOT NULL DEFAULT 'zaplanowany',
  `notes`          TEXT NULL,
  `created_by`     INT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_fm_club` (`club_id`),
  KEY `idx_fm_date` (`match_date`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)          ON DELETE CASCADE,
  FOREIGN KEY (`home_team_id`)  REFERENCES `football_teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`away_team_id`)  REFERENCES `football_teams`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `football_match_events` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id`   INT UNSIGNED NOT NULL,
  `member_id`  INT UNSIGNED NOT NULL,
  `minute`     TINYINT UNSIGNED NULL,
  `type`       ENUM('gol','asysta','zolta_kartka','czerwona_kartka','zmiana_wejscie','zmiana_zejscie','kontuzja','karny_strzelony','karny_obroniony') NOT NULL,
  `notes`      VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_fme_match` (`match_id`),
  FOREIGN KEY (`match_id`)  REFERENCES `football_matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `football_lineups` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id`      INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `team_id`       INT UNSIGNED NOT NULL,
  `position`      ENUM('BR','OB','PM','NA','SR','LS','PS','LO','PO','SO','N') NULL COMMENT 'bramkarz/obrona/pomoc/atak',
  `is_starter`    TINYINT(1) NOT NULL DEFAULT 1,
  `jersey_number` TINYINT UNSIGNED NULL,
  UNIQUE KEY `uq_lineup` (`match_id`, `member_id`),
  FOREIGN KEY (`match_id`)  REFERENCES `football_matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)          ON DELETE CASCADE,
  FOREIGN KEY (`team_id`)   REFERENCES `football_teams`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `football_transfers` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`       INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `direction`     ENUM('przychodzacy','odchodzacy','wypozyczenie') NOT NULL,
  `from_club`     VARCHAR(150) NULL,
  `to_club`       VARCHAR(150) NULL,
  `transfer_date` DATE NOT NULL,
  `fee`           DECIMAL(12,2) NULL,
  `contract_until` DATE NULL,
  `notes`         TEXT NULL,
  `created_by`    INT UNSIGNED NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ftrans_club` (`club_id`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)  REFERENCES `members`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
