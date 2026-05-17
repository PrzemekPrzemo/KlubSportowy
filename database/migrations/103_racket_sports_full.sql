-- 103_racket_sports_full.sql
-- ============================================================
-- Promocja 5 sportów PARTIAL -> FULL: badminton, squash, golf, padel, archery.
--
-- Dodaje tabele:
--   - badminton: per-set match stats + member rakieta (BWF points)
--   - squash:    per-set PAR match stats (best-of-5)
--   - golf:      kursy, scorecardy (per-dolek JSON), member handicap WHS
--   - padel:     pary (doubles) z ranking points + UNIQUE pair
--   - archery:   scorecardy per dystans/end z totalami 10s/Xs + member equipment
--
-- Wszystkie tabele club-scoped (club_id NOT NULL, ON DELETE CASCADE).
-- Bezpieczne IF NOT EXISTS — można uruchomić wielokrotnie.
-- ============================================================

SET foreign_key_checks = 0;

-- ----------------------------------------------------------------
-- BADMINTON: per-set match stats + member profile
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_badminton_match_stats` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `match_id`     INT UNSIGNED NOT NULL,
    `club_id`      INT UNSIGNED NOT NULL,
    `set_number`   TINYINT NOT NULL COMMENT '1..3 (best-of-3, 21pkt)',
    `home_score`   INT NULL,
    `away_score`   INT NULL,
    `duration_min` INT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_bm_match_set` (`match_id`, `set_number`),
    KEY `idx_bm_stats_club` (`club_id`),
    FOREIGN KEY (`match_id`) REFERENCES `tournament_matches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_badminton_member` (
    `member_id`     INT UNSIGNED NOT NULL PRIMARY KEY,
    `club_id`       INT UNSIGNED NOT NULL,
    `discipline`    SET('singles','doubles','mixed') DEFAULT 'singles',
    `hand`          ENUM('right','left') DEFAULT 'right',
    `bwf_points`    INT DEFAULT 0,
    `national_rank` INT NULL,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_bm_member_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- SQUASH: per-set PAR match stats (best-of-5, 11pkt)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_squash_match_stats` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `match_id`   INT UNSIGNED NOT NULL,
    `club_id`    INT UNSIGNED NOT NULL,
    `set_number` TINYINT NOT NULL COMMENT '1..5 (best-of-5, 11pkt PAR)',
    `home_score` INT NULL,
    `away_score` INT NULL,
    `lets`       INT DEFAULT 0,
    `strokes`    INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_sq_match_set` (`match_id`, `set_number`),
    KEY `idx_sq_stats_club` (`club_id`),
    FOREIGN KEY (`match_id`) REFERENCES `tournament_matches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- GOLF: kursy / scorecardy / member handicap
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_golf_courses` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`     INT UNSIGNED NOT NULL,
    `name`        VARCHAR(200) NOT NULL,
    `city`        VARCHAR(100) NULL,
    `holes_count` INT DEFAULT 18,
    `par_total`   INT DEFAULT 72,
    `rating`      DECIMAL(4,1) NULL,
    `slope`       INT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sgc_club` (`club_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_golf_scorecards` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`          INT UNSIGNED NOT NULL,
    `member_id`        INT UNSIGNED NOT NULL,
    `course_id`        INT UNSIGNED NULL,
    `played_at`        DATE NOT NULL,
    `total_strokes`    INT NULL,
    `total_to_par`     INT NULL,
    `handicap_used`    DECIMAL(4,1) NULL,
    `hole_scores_json` JSON NULL COMMENT 'tablica 18 dolkow [4,5,3,...]',
    `verified`         TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'klub potwierdzil self-report',
    `verified_by`      INT UNSIGNED NULL,
    `verified_at`      DATETIME NULL,
    `notes`            TEXT NULL,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sgs_member_date` (`member_id`, `played_at`),
    KEY `idx_sgs_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `sport_golf_courses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_golf_member` (
    `member_id`      INT UNSIGNED NOT NULL PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `hcp`            DECIMAL(4,1) DEFAULT 36.0,
    `hcp_updated_at` DATE NULL,
    `pga_license`    VARCHAR(50) NULL,
    `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_sgm_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- PADEL: pary (doubles) z ranking points
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_padel_pairs` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `member_a_id`    INT UNSIGNED NOT NULL,
    `member_b_id`    INT UNSIGNED NOT NULL,
    `pair_name`      VARCHAR(200) NULL,
    `ranking_points` INT DEFAULT 0,
    `active`         TINYINT(1) DEFAULT 1,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_spp_pair` (`member_a_id`, `member_b_id`),
    KEY `idx_spp_club_active` (`club_id`, `active`),
    FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_a_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_b_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- ARCHERY: member equipment + scorecard rounds
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_archery_member` (
    `member_id`     INT UNSIGNED NOT NULL PRIMARY KEY,
    `club_id`       INT UNSIGNED NOT NULL,
    `bow_type`      ENUM('recurve','compound','barebow','longbow') DEFAULT 'recurve',
    `dominant_eye`  ENUM('right','left') DEFAULT 'right',
    `national_rank` INT NULL,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_sam_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_archery_scorecards` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `member_id`      INT UNSIGNED NOT NULL,
    `shot_at`        DATE NOT NULL,
    `distance_m`     INT NOT NULL COMMENT '18 indoor / 70 outdoor / 90 long',
    `arrows_per_end` INT DEFAULT 6,
    `total_ends`     INT NOT NULL,
    `scores_json`    JSON NULL COMMENT 'tablica end-ow: [[10,9,X,8,7,7],...]',
    `total_score`    INT NULL,
    `tens`           INT DEFAULT 0,
    `x_count`        INT DEFAULT 0,
    `verified`       TINYINT(1) NOT NULL DEFAULT 0,
    `verified_by`    INT UNSIGNED NULL,
    `verified_at`    DATETIME NULL,
    `notes`          TEXT NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sas_member_date` (`member_id`, `shot_at`),
    KEY `idx_sas_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
