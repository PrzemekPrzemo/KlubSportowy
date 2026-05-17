-- Migration 104: Promocja STUB -> FULL dla trzech sportow:
--   * E-SPORT (gaming competitive, multi-game catalog)
--   * DANCE   (scoring/judging — judges + performances)
--   * CANOEING (timing-based — sprint/slalom)
--
-- Wzorzec multi-tenant: kazda tabela ma `club_id` (NULL = globalny katalog
-- dla esport/dance), kazdy SELECT/INSERT/DELETE/UPDATE w modelach klubowych
-- filtruje po `club_id` (ClubScopedModel auto-injects).

SET foreign_key_checks = 0;

-- ============================================================
-- E-SPORT — multi-game catalog + member profiles + match details
-- ============================================================

CREATE TABLE IF NOT EXISTS `sport_esport_games` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NULL COMMENT 'NULL=globalna, INT=klubowa wlasna',
    `game_code`       VARCHAR(50) NOT NULL,
    `display_name`    VARCHAR(200) NOT NULL,
    `genre`           ENUM('fps','moba','sports','rts','fighting','battle_royale','racing','other') NOT NULL DEFAULT 'other',
    `team_size`       INT NOT NULL DEFAULT 1,
    `ranking_system`  ENUM('elo','points','league_rank','custom') NOT NULL DEFAULT 'elo',
    `default_format`  ENUM('single_elim','double_elim','round_robin','swiss','bracket_8','bracket_16') NOT NULL DEFAULT 'single_elim',
    `metadata_json`   JSON NULL,
    `active`          TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_esport_games_club_active` (`club_id`, `active`),
    KEY `idx_esport_games_code` (`game_code`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_esport_member_profiles` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `member_id`      INT UNSIGNED NOT NULL,
    `club_id`        INT UNSIGNED NOT NULL,
    `game_code`      VARCHAR(50) NOT NULL,
    `in_game_name`   VARCHAR(200) NOT NULL,
    `platform`       ENUM('pc','xbox','playstation','switch','mobile','other') NOT NULL DEFAULT 'pc',
    `rank_tier`      VARCHAR(50) NULL COMMENT 'np. Diamond II, Gold IV',
    `elo_rating`     INT NOT NULL DEFAULT 1000,
    `hours_played`   INT NOT NULL DEFAULT 0,
    `wins`           INT NOT NULL DEFAULT 0,
    `losses`         INT NOT NULL DEFAULT 0,
    `stream_url`     VARCHAR(500) NULL COMMENT 'Twitch/YouTube channel URL',
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_member_game` (`member_id`, `game_code`),
    KEY `idx_esport_prof_club_game` (`club_id`, `game_code`),
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_esport_match_details` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `match_id`       INT UNSIGNED NOT NULL,
    `club_id`        INT UNSIGNED NOT NULL,
    `game_code`      VARCHAR(50) NOT NULL,
    `map_name`       VARCHAR(100) NULL,
    `duration_min`   INT NULL,
    `home_score`     INT NULL,
    `away_score`     INT NULL,
    `stream_url`     VARCHAR(500) NULL COMMENT 'Twitch/YouTube live URL',
    `vod_url`        VARCHAR(500) NULL COMMENT 'replay/highlight after match',
    `metadata_json`  JSON NULL COMMENT 'kills/deaths/assists itp.',
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_esport_md_match` (`match_id`),
    KEY `idx_esport_md_club` (`club_id`),
    FOREIGN KEY (`match_id`) REFERENCES `tournament_matches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`)              ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed top 10 popular esport games (global, club_id = NULL).
-- INSERT IGNORE — idempotentnie (UNIQUE na (club_id, game_code) wymagaby tu indexu;
-- robimy delikatnie: tylko jesli jeszcze brak globalnego wpisu).
INSERT INTO `sport_esport_games` (`club_id`, `game_code`, `display_name`, `genre`, `team_size`, `ranking_system`, `default_format`)
SELECT * FROM (
    SELECT NULL AS club_id, 'fifa' AS game_code, 'FIFA / EA Sports FC' AS display_name, 'sports' AS genre, 1 AS team_size, 'elo' AS rsys, 'double_elim' AS fmt UNION ALL
    SELECT NULL, 'cs2',         'Counter-Strike 2',  'fps',           5, 'elo',         'double_elim' UNION ALL
    SELECT NULL, 'lol',         'League of Legends', 'moba',          5, 'league_rank', 'single_elim' UNION ALL
    SELECT NULL, 'valorant',    'Valorant',          'fps',           5, 'league_rank', 'double_elim' UNION ALL
    SELECT NULL, 'dota2',       'Dota 2',            'moba',          5, 'elo',         'single_elim' UNION ALL
    SELECT NULL, 'rocket_league','Rocket League',    'sports',        3, 'elo',         'double_elim' UNION ALL
    SELECT NULL, 'apex',        'Apex Legends',      'battle_royale', 3, 'points',      'round_robin' UNION ALL
    SELECT NULL, 'overwatch2',  'Overwatch 2',       'fps',           5, 'league_rank', 'double_elim' UNION ALL
    SELECT NULL, 'starcraft2',  'StarCraft II',      'rts',           1, 'elo',         'single_elim' UNION ALL
    SELECT NULL, 'tekken8',     'Tekken 8',          'fighting',      1, 'elo',         'double_elim'
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM `sport_esport_games` g
    WHERE g.club_id IS NULL AND g.game_code = seed.game_code
);


-- ============================================================
-- DANCE — styles catalog + member styles + performances + judges
-- ============================================================

CREATE TABLE IF NOT EXISTS `sport_dance_styles` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`      INT UNSIGNED NULL,
    `style_code`   VARCHAR(50) NOT NULL,
    `display_name` VARCHAR(200) NOT NULL,
    `category`     ENUM('ballroom','latin','street','contemporary','folk','other') NOT NULL DEFAULT 'other',
    `active`       TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_dance_styles_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_dance_member_styles` (
    `member_id`         INT UNSIGNED NOT NULL,
    `club_id`           INT UNSIGNED NOT NULL,
    `style_code`        VARCHAR(50) NOT NULL,
    `level`             ENUM('beginner','intermediate','advanced','professional') NOT NULL DEFAULT 'beginner',
    `partner_member_id` INT UNSIGNED NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`member_id`, `style_code`),
    KEY `idx_dance_ms_club_style` (`club_id`, `style_code`),
    FOREIGN KEY (`member_id`)         REFERENCES `members`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)           REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`partner_member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_dance_performances` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tournament_id`       INT UNSIGNED NOT NULL,
    `club_id`             INT UNSIGNED NOT NULL,
    `member_id`           INT UNSIGNED NOT NULL,
    `partner_member_id`   INT UNSIGNED NULL,
    `style_code`          VARCHAR(50) NOT NULL,
    `performance_number`  INT NULL,
    `total_score`         DECIMAL(5,2) NULL,
    `rank`                INT NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_dance_perf_tour_score` (`tournament_id`, `total_score` DESC),
    KEY `idx_dance_perf_club` (`club_id`),
    FOREIGN KEY (`tournament_id`)     REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)           REFERENCES `clubs`(`id`)        ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)         REFERENCES `members`(`id`)      ON DELETE CASCADE,
    FOREIGN KEY (`partner_member_id`) REFERENCES `members`(`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_dance_judge_scores` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `performance_id`    INT UNSIGNED NOT NULL,
    `judge_name`        VARCHAR(200) NOT NULL,
    `technique_score`   DECIMAL(4,2) NULL,
    `artistry_score`    DECIMAL(4,2) NULL,
    `difficulty_score`  DECIMAL(4,2) NULL,
    `total_score`       DECIMAL(5,2) NULL,
    `notes`             TEXT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_dance_js_performance` (`performance_id`),
    FOREIGN KEY (`performance_id`) REFERENCES `sport_dance_performances`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed dance styles (global, club_id = NULL).
INSERT INTO `sport_dance_styles` (`club_id`, `style_code`, `display_name`, `category`)
SELECT * FROM (
    SELECT NULL AS club_id, 'waltz' AS style_code, 'Walc angielski' AS display_name, 'ballroom' AS category UNION ALL
    SELECT NULL, 'viennese_waltz', 'Walc wiedenski', 'ballroom' UNION ALL
    SELECT NULL, 'tango',          'Tango',          'ballroom' UNION ALL
    SELECT NULL, 'foxtrot',        'Fokstrot',       'ballroom' UNION ALL
    SELECT NULL, 'quickstep',      'Quickstep',      'ballroom' UNION ALL
    SELECT NULL, 'samba',          'Samba',          'latin' UNION ALL
    SELECT NULL, 'cha_cha',        'Cha-cha',        'latin' UNION ALL
    SELECT NULL, 'rumba',          'Rumba',          'latin' UNION ALL
    SELECT NULL, 'paso_doble',     'Paso doble',     'latin' UNION ALL
    SELECT NULL, 'jive',           'Jive',           'latin' UNION ALL
    SELECT NULL, 'hip_hop',        'Hip-hop',        'street' UNION ALL
    SELECT NULL, 'contemporary',   'Contemporary',   'contemporary'
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM `sport_dance_styles` s
    WHERE s.club_id IS NULL AND s.style_code = seed.style_code
);


-- ============================================================
-- CANOEING — boat classes per member + timing-based race results
-- ============================================================

CREATE TABLE IF NOT EXISTS `sport_canoeing_member` (
    `member_id`     INT UNSIGNED NOT NULL PRIMARY KEY,
    `club_id`       INT UNSIGNED NOT NULL,
    `boat_class`    ENUM('K1','K2','K4','C1','C2','C4','slalom') NOT NULL DEFAULT 'K1',
    `weight_class`  VARCHAR(50) NULL,
    `national_rank` INT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_canoeing_member_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_canoeing_race_results` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tournament_id`      INT UNSIGNED NOT NULL,
    `club_id`            INT UNSIGNED NOT NULL,
    `member_id`          INT UNSIGNED NOT NULL,
    `distance_m`         INT NOT NULL,
    `boat_class`         VARCHAR(20) NOT NULL,
    `finish_time_ms`     INT NOT NULL,
    `penalties_seconds`  DECIMAL(5,2) NOT NULL DEFAULT 0,
    `rank`               INT NULL,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_canoeing_rr_tour_time` (`tournament_id`, `finish_time_ms` ASC),
    KEY `idx_canoeing_rr_club` (`club_id`),
    FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)        ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
