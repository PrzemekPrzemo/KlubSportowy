-- 105_timing_sports_full.sql
-- ============================================================
-- Promocja 11 sportów typu TIMING (PARTIAL → FULL) oraz
-- 2 sportów typu STRENGTH (strongman STUB → FULL, powerlifting PARTIAL → FULL).
--
-- WSPÓLNY (discriminator-based) model danych — zamiast 11 tabel per-sport
-- używamy jednej tabeli `sport_timing_results` z kolumną `sport_key`
-- oraz sport-specific metadanych w polu `metadata_json`.
--
-- Multi-tenant strict: każda tabela posiada `club_id` (FK do clubs)
-- + indeks pomocniczy (club_id, sport_key).
-- ============================================================

SET foreign_key_checks = 0;

-- -----------------------------------------------------------------
-- Profile zawodnika dla wszystkich sportów typu timing
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_timing_member_profiles` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `member_id`            INT UNSIGNED NOT NULL,
    `club_id`              INT UNSIGNED NOT NULL,
    `sport_key`            VARCHAR(50)  NOT NULL,
    `specialty`            VARCHAR(100) NULL COMMENT 'dla swimming: butterfly/freestyle; dla biathlon: sprint/individual itd.',
    `best_time_ms`         INT          NULL COMMENT 'personal best na referencyjnym dystansie (ms)',
    `best_time_distance_m` INT          NULL,
    `national_rank`        INT          NULL,
    `metadata_json`        JSON         NULL COMMENT 'sport-specific: snow style, paddle hand, boat class etc.',
    `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_member_sport` (`member_id`, `sport_key`),
    KEY `idx_club_sport` (`club_id`, `sport_key`),
    CONSTRAINT `fk_stmp_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stmp_club`   FOREIGN KEY (`club_id`)   REFERENCES `clubs`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Profile zawodnika per timing-sport (PB, specialty, metadata).';

-- -----------------------------------------------------------------
-- Wspólna tabela wyników dla wszystkich timing sportów
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_timing_results` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tournament_id`       INT UNSIGNED NULL,
    `training_id`         INT UNSIGNED NULL COMMENT 'opcjonalnie — czas z treningu',
    `club_id`             INT UNSIGNED NOT NULL,
    `member_id`           INT UNSIGNED NOT NULL,
    `sport_key`           VARCHAR(50)  NOT NULL,
    `event_name`          VARCHAR(200) NOT NULL COMMENT 'np. "100m freestyle", "Road race U23"',
    `distance_m`          INT          NOT NULL,
    `finish_time_ms`      INT          NOT NULL,
    `splits_json`         JSON         NULL COMMENT 'cząstkowe czasy (per lap, per leg)',
    `penalties_seconds`   DECIMAL(5,2) NOT NULL DEFAULT 0,
    `rank`                INT          NULL,
    `category`            VARCHAR(100) NULL,
    `weather_conditions`  VARCHAR(200) NULL COMMENT 'dla outdoor sports',
    `recorded_at`         DATE         NOT NULL,
    `verified`            TINYINT(1)   NOT NULL DEFAULT 0,
    `metadata_json`       JSON         NULL,
    `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_member_sport_date` (`member_id`, `sport_key`, `recorded_at` DESC),
    KEY `idx_club_sport`        (`club_id`, `sport_key`),
    KEY `idx_tournament_time`   (`tournament_id`, `finish_time_ms` ASC),
    CONSTRAINT `fk_str_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_str_club`       FOREIGN KEY (`club_id`)       REFERENCES `clubs`       (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_str_member`     FOREIGN KEY (`member_id`)     REFERENCES `members`     (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wspólne wyniki timing sportów (swimming/cycling/rowing/triathlon itd.).';

-- -----------------------------------------------------------------
-- Strength sports — profil siły (strongman / powerlifting / weightlifting)
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_strength_member` (
    `member_id`     INT UNSIGNED NOT NULL PRIMARY KEY,
    `club_id`       INT UNSIGNED NOT NULL,
    `sport_key`     VARCHAR(50)  NOT NULL COMMENT 'strongman | powerlifting | weightlifting',
    `weight_class`  VARCHAR(50)  NULL,
    `body_weight_kg` DECIMAL(5,2) NULL,
    `squat_pb_kg`   DECIMAL(5,2) NULL,
    `bench_pb_kg`   DECIMAL(5,2) NULL,
    `deadlift_pb_kg` DECIMAL(5,2) NULL,
    `total_pb_kg`   DECIMAL(6,2) NULL,
    `wilks_score`   DECIMAL(7,2) NULL,
    `metadata_json` JSON         NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_club_sport` (`club_id`, `sport_key`),
    CONSTRAINT `fk_ssm_club`   FOREIGN KEY (`club_id`)   REFERENCES `clubs`   (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ssm_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Profile zawodnika strength-sport: PB squat/bench/deadlift + Wilks.';

-- -----------------------------------------------------------------
-- Strength sports — pojedyncze podejścia (attempts)
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sport_strength_attempts` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `member_id`      INT UNSIGNED NOT NULL,
    `tournament_id`  INT UNSIGNED NULL,
    `sport_key`      VARCHAR(50)  NOT NULL DEFAULT 'powerlifting',
    `lift_type`      VARCHAR(50)  NOT NULL COMMENT 'squat/bench/deadlift OR strongman event',
    `attempt_number` TINYINT      NOT NULL,
    `weight_kg`      DECIMAL(5,2) NULL,
    `reps`           INT          NOT NULL DEFAULT 1,
    `success`        TINYINT(1)   NOT NULL DEFAULT 0,
    `notes`          TEXT         NULL,
    `attempted_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_member_lift` (`member_id`, `lift_type`),
    KEY `idx_tournament`  (`tournament_id`),
    KEY `idx_club_sport`  (`club_id`, `sport_key`),
    CONSTRAINT `fk_ssa_club`       FOREIGN KEY (`club_id`)       REFERENCES `clubs`       (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ssa_member`     FOREIGN KEY (`member_id`)     REFERENCES `members`     (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ssa_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Podejścia w strength-sportach (powerlifting, strongman, weightlifting).';

SET foreign_key_checks = 1;
