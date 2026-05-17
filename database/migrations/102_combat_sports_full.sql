-- ============================================================
-- 102_combat_sports_full.sql
-- Combat sports promotion PARTIAL -> FULL
-- Sporty: boxing, mma, wrestling, fencing
--
-- Wprowadza:
--   * boxing: license levels, fight record (W-L-D), weight history
--   * mma: fight record + discipline mix
--   * wrestling: styles, technical match breakdown
--   * fencing: weapons multi-select + FIE rank + pools dla DE
--
-- Bezpieczenstwo: kazda tabela ma club_id (multi-tenant).
-- ============================================================

SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- BOXING — kartoteka bokserska
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_boxing_member_record` (
    `member_id`            INT UNSIGNED NOT NULL PRIMARY KEY,
    `club_id`              INT UNSIGNED NOT NULL,
    `wins`                 INT NOT NULL DEFAULT 0,
    `losses`               INT NOT NULL DEFAULT 0,
    `draws`                INT NOT NULL DEFAULT 0,
    `ko_wins`              INT NOT NULL DEFAULT 0,
    `tko_wins`             INT NOT NULL DEFAULT 0,
    `license_level`        ENUM('junior','senior','elite','professional') NOT NULL DEFAULT 'junior',
    `license_number`       VARCHAR(50) DEFAULT NULL,
    `license_expires`      DATE DEFAULT NULL,
    `current_weight_class` VARCHAR(50) DEFAULT NULL,
    `current_weight_kg`    DECIMAL(5,2) DEFAULT NULL,
    `reach_cm`             INT DEFAULT NULL,
    `stance`               ENUM('orthodox','southpaw','switch') NOT NULL DEFAULT 'orthodox',
    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_sbmr_club`     (`club_id`),
    KEY `idx_sbmr_lic_exp`  (`license_expires`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_boxing_weight_history` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `member_id`    INT UNSIGNED NOT NULL,
    `club_id`      INT UNSIGNED NOT NULL,
    `weight_kg`    DECIMAL(5,2) NOT NULL,
    `weight_class` VARCHAR(50) DEFAULT NULL,
    `measured_at`  DATE NOT NULL,
    `notes`        TEXT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sbwh_member_date` (`member_id`,`measured_at`),
    KEY `idx_sbwh_club`        (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- MMA — kartoteka zawodnika
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_mma_member_record` (
    `member_id`            INT UNSIGNED NOT NULL PRIMARY KEY,
    `club_id`              INT UNSIGNED NOT NULL,
    `wins`                 INT NOT NULL DEFAULT 0,
    `losses`               INT NOT NULL DEFAULT 0,
    `draws`                INT NOT NULL DEFAULT 0,
    `ko_wins`              INT NOT NULL DEFAULT 0,
    `sub_wins`             INT NOT NULL DEFAULT 0,
    `dec_wins`             INT NOT NULL DEFAULT 0,
    `current_weight_class` VARCHAR(50) DEFAULT NULL,
    `stance`               ENUM('orthodox','southpaw','switch') NOT NULL DEFAULT 'orthodox',
    `reach_cm`             INT DEFAULT NULL,
    `pct_striking`         TINYINT NOT NULL DEFAULT 33,
    `pct_wrestling`        TINYINT NOT NULL DEFAULT 33,
    `pct_grappling`        TINYINT NOT NULL DEFAULT 34,
    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_smmr_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- WRESTLING — kartoteka + technical breakdown
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_wrestling_member` (
    `member_id`            INT UNSIGNED NOT NULL PRIMARY KEY,
    `club_id`              INT UNSIGNED NOT NULL,
    `styles`               SET('freestyle','greco_roman','womens') NOT NULL DEFAULT 'freestyle',
    `current_weight_kg`    DECIMAL(5,2) DEFAULT NULL,
    `current_weight_class` VARCHAR(50) DEFAULT NULL,
    `rank_points`          INT NOT NULL DEFAULT 0,
    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_swm_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_wrestling_match_breakdown` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `match_id`        INT UNSIGNED NOT NULL,
    `club_id`         INT UNSIGNED NOT NULL,
    `member_id`       INT UNSIGNED NOT NULL,
    `takedowns`       INT NOT NULL DEFAULT 0,
    `exposures`       INT NOT NULL DEFAULT 0,
    `escapes`         INT NOT NULL DEFAULT 0,
    `technical_fall`  TINYINT(1) NOT NULL DEFAULT 0,
    `pin`             TINYINT(1) NOT NULL DEFAULT 0,
    `caution_count`   INT NOT NULL DEFAULT 0,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_swmb_match`  (`match_id`),
    KEY `idx_swmb_member` (`member_id`),
    KEY `idx_swmb_club`   (`club_id`),
    FOREIGN KEY (`match_id`)  REFERENCES `tournament_matches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)              ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)            ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- FENCING — multi-weapon profil + ranking + pools (DE)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_fencing_member` (
    `member_id`  INT UNSIGNED NOT NULL PRIMARY KEY,
    `club_id`    INT UNSIGNED NOT NULL,
    `weapons`    SET('epee','foil','sabre') NOT NULL DEFAULT 'epee',
    `fie_rank`   INT DEFAULT NULL,
    `hand`       ENUM('right','left') NOT NULL DEFAULT 'right',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_sfm_club` (`club_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sport_fencing_pools` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tournament_id` INT UNSIGNED NOT NULL,
    `club_id`       INT UNSIGNED NOT NULL,
    `pool_number`   INT NOT NULL,
    `weapon`        ENUM('epee','foil','sabre') NOT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_sfp_tournament_pool` (`tournament_id`,`pool_number`),
    KEY `idx_sfp_tournament` (`tournament_id`),
    KEY `idx_sfp_club`       (`club_id`),
    FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
