-- Equestrian Q.2: Riders z licencja PZJ + rozszerzenie pair-assignments
--
-- Dotychczas equestrian_assignments laczy member_id z horse_id (= de facto
-- pair). Dodajemy nadrzedny rejestr equestrian_riders ktory:
--   - rejestruje zawodnika z licencja PZJ (license_no, license_class,
--     valid_until)
--   - ma extra fields specyficzne dla jezdzca: weight_kg, height_cm,
--     handedness (lewak/prawak ma znaczenie przy doborze konia)
--   - status (aktywny/zawieszony/wycofany)
--
-- equestrian_assignments dostaje optional rider_id FK — backward compat
-- (assignments z member_id ale bez rider_id sa "casual" — bez wymagania
-- licencji PZJ, np. jezdzcy rekreacyjni).

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `equestrian_riders` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NOT NULL,
    `member_id`       INT UNSIGNED NOT NULL UNIQUE COMMENT '1:1 z members',
    `license_no`      VARCHAR(40) NULL COMMENT 'numer licencji PZJ',
    `license_class`   ENUM('B','S1','S2','S3','S4','PRO','PARA') NULL
                      COMMENT 'klasy PZJ: B=podstawowa, S1-S4=sportowa, PRO=zawodowa, PARA=parajezdziectwo',
    `license_valid_until` DATE NULL,
    `discipline_main` ENUM('dressage','jumping','eventing','endurance','reining','vaulting','driving','para') NULL,
    `weight_kg`       DECIMAL(5,2) NULL COMMENT 'waga zawodnika (wymóg w niektórych klasach)',
    `height_cm`       SMALLINT UNSIGNED NULL,
    `handedness`      ENUM('left','right') NULL,
    `status`          ENUM('aktywny','zawieszony','wycofany') NOT NULL DEFAULT 'aktywny',
    `notes`           TEXT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_eqr_club`     (`club_id`),
    KEY `idx_eqr_license`  (`license_no`),
    KEY `idx_eqr_valid`    (`license_valid_until`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zawodnicy jezdziectwa z licencjami PZJ';

-- Rozszerzenie equestrian_assignments o rider_id (optional)
ALTER TABLE `equestrian_assignments`
    ADD COLUMN `rider_id`   INT UNSIGNED NULL AFTER `member_id`,
    ADD COLUMN `is_primary` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'glowna para (czyli ta, na ktorej zawodnik startuje na zawodach)' AFTER `rider_id`;

ALTER TABLE `equestrian_assignments`
    ADD KEY `idx_eqa_rider` (`rider_id`),
    ADD CONSTRAINT `fk_eqa_rider`
        FOREIGN KEY (`rider_id`) REFERENCES `equestrian_riders`(`id`) ON DELETE SET NULL;

SET foreign_key_checks = 1;
