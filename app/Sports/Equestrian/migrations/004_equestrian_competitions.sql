-- Equestrian Q.5: competitions + classes + starts + result extensions
--
-- Dotychczas equestrian_results bylo flat (competition_name + date + class_level
-- jako VARCHAR-y). Wprowadzamy 3 nowe tabele opisujace strukture zawodow PZJ:
--
--   competitions             — zawody (CSI/CDI/CSO/CDN itp.)
--     └── competition_classes — klasy w zawodach (np. Klasa LL/L/P/N1/N2/C/CC)
--          └── starts          — start pary rider+horse w klasie
--               └── results    — wynik startu (FK z istniejacego results)
--
-- Backward compat: existing equestrian_results zostaje, dostaje optional FK-i.
-- Stare wpisy z VARCHAR-ami nie sa migrowane — uzytkownicy moga edytowac je
-- recznie linkujac do competitions ktore sami stworza.

SET foreign_key_checks = 0;

-- 1. Zawody (entity)
CREATE TABLE IF NOT EXISTS `equestrian_competitions` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`    INT UNSIGNED NOT NULL,
    `name`       VARCHAR(200) NOT NULL,
    `date_from`  DATE NOT NULL,
    `date_to`    DATE NULL,
    `location`   VARCHAR(200) NULL,
    `level`      ENUM('klubowe','regionalne','krajowe','CDN','CDI','CSO','CSI','CIC','CCI','CEI','para','inne')
                 NOT NULL DEFAULT 'klubowe',
    `status`     ENUM('zaplanowane','w_trakcie','zakonczone','odwolane') NOT NULL DEFAULT 'zaplanowane',
    `host_club`  VARCHAR(200) NULL COMMENT 'jesli zawody nie sa nasze',
    `notes`      TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_eqc_club` (`club_id`),
    KEY `idx_eqc_date` (`date_from`),
    FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zawody jezdzieckie';

-- 2. Klasy w zawodach
CREATE TABLE IF NOT EXISTS `equestrian_competition_classes` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `competition_id`  INT UNSIGNED NOT NULL,
    `class_no`        SMALLINT UNSIGNED NULL COMMENT 'numer klasy w programie zawodow',
    `name`            VARCHAR(200) NOT NULL,
    `discipline`      ENUM('dressage','jumping','eventing','endurance','reining','vaulting','driving','para')
                      NOT NULL,
    `class_level`     VARCHAR(40) NULL COMMENT 'np. LL / L / P / N1 / N2 / C / CC / Grand Prix',
    `fence_height_cm` SMALLINT UNSIGNED NULL COMMENT 'wysokosc przeszkod (skoki)',
    `time_allowed_s`  SMALLINT UNSIGNED NULL COMMENT 'norma czasu (skoki/cross)',
    `max_starters`    SMALLINT UNSIGNED NULL,
    `prize_pool`      DECIMAL(10,2) NULL,
    `notes`           TEXT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_eqcc_comp`       (`competition_id`),
    KEY `idx_eqcc_discipline` (`discipline`),
    FOREIGN KEY (`competition_id`) REFERENCES `equestrian_competitions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Klasy w zawodach jezdzieckich';

-- 3. Starty (rider+horse w klasie)
CREATE TABLE IF NOT EXISTS `equestrian_starts` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`               INT UNSIGNED NOT NULL,
    `competition_class_id`  INT UNSIGNED NOT NULL,
    `rider_id`              INT UNSIGNED NULL COMMENT 'NULL = casual rider bez licencji',
    `member_id`             INT UNSIGNED NULL COMMENT 'fallback gdy rider_id IS NULL',
    `horse_id`              INT UNSIGNED NULL,
    `horse_name_text`       VARCHAR(150) NULL COMMENT 'fallback gdy horse spoza naszego klubu',
    `start_no`              SMALLINT UNSIGNED NULL COMMENT 'numer startowy w klasie',
    `status`                ENUM('zgloszony','potwierdzony','startuje','wycofany','dyskwalifikacja','eliminacja')
                            NOT NULL DEFAULT 'zgloszony',
    `notes`                 TEXT NULL,
    `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_eqs_class`  (`competition_class_id`),
    KEY `idx_eqs_club`   (`club_id`),
    KEY `idx_eqs_rider`  (`rider_id`),
    KEY `idx_eqs_horse`  (`horse_id`),
    FOREIGN KEY (`club_id`)              REFERENCES `clubs`(`id`)                        ON DELETE CASCADE,
    FOREIGN KEY (`competition_class_id`) REFERENCES `equestrian_competition_classes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`rider_id`)             REFERENCES `equestrian_riders`(`id`)            ON DELETE SET NULL,
    FOREIGN KEY (`member_id`)            REFERENCES `members`(`id`)                      ON DELETE SET NULL,
    FOREIGN KEY (`horse_id`)             REFERENCES `equestrian_horses`(`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Starty pary rider+horse w klasie zawodow';

-- 4. Rozszerzenie equestrian_results o linki do nowej hierarchii
ALTER TABLE `equestrian_results`
    ADD COLUMN `competition_id`        INT UNSIGNED NULL AFTER `member_id`,
    ADD COLUMN `competition_class_id`  INT UNSIGNED NULL AFTER `competition_id`,
    ADD COLUMN `start_id`              INT UNSIGNED NULL AFTER `competition_class_id`,
    ADD COLUMN `rider_id`              INT UNSIGNED NULL AFTER `start_id`;

ALTER TABLE `equestrian_results`
    ADD KEY `idx_eqr_competition`        (`competition_id`),
    ADD KEY `idx_eqr_competition_class`  (`competition_class_id`),
    ADD KEY `idx_eqr_start`              (`start_id`),
    ADD KEY `idx_eqr_rider`              (`rider_id`),
    ADD CONSTRAINT `fk_eqr_competition`
        FOREIGN KEY (`competition_id`) REFERENCES `equestrian_competitions`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_eqr_competition_class`
        FOREIGN KEY (`competition_class_id`) REFERENCES `equestrian_competition_classes`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_eqr_start`
        FOREIGN KEY (`start_id`) REFERENCES `equestrian_starts`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_eqr_rider`
        FOREIGN KEY (`rider_id`) REFERENCES `equestrian_riders`(`id`) ON DELETE SET NULL;

SET foreign_key_checks = 1;
