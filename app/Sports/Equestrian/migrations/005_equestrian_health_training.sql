-- Equestrian Q.7: status weterynaryjny konia + dziennik treningu konia
--
-- Dwie powiazane tabele dla full-domain coverage konia:
--   equestrian_horse_health   — kontrole vet, szczepienia, kontuzje, zabiegi
--   equestrian_horse_training — sesje treningowe konia (lonza/ujezdzenie/skok/spacer)
--
-- Daty waznosci szczepien (wymog PZJ + FEI dla startow): grypa konska
-- 6-12 mies, tezec 2 lata, pieczec FEI co rok. Bez aktualnej szczepionki
-- kon nie moze startowac w zawodach krajowych.

SET foreign_key_checks = 0;

-- 1. Status zdrowotny konia (event-log)
CREATE TABLE IF NOT EXISTS `equestrian_horse_health` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`      INT UNSIGNED NOT NULL,
    `horse_id`     INT UNSIGNED NOT NULL,
    `event_date`   DATE NOT NULL,
    `event_type`   ENUM(
        'szczepienie_grypa',
        'szczepienie_tezec',
        'szczepienie_inne',
        'odrobaczanie',
        'kontrola_vet',
        'badanie_krwi',
        'kontuzja',
        'zabieg',
        'rentgen',
        'inny'
    ) NOT NULL,
    `description`  VARCHAR(500) NULL,
    `vet_name`     VARCHAR(150) NULL,
    `vet_license`  VARCHAR(40)  NULL COMMENT 'numer prawa wykonywania zawodu',
    `valid_until`  DATE NULL COMMENT 'data waznosci (dla szczepien) lub kolejnego badania',
    `cost`         DECIMAL(8,2) NULL,
    `document_path` VARCHAR(255) NULL COMMENT 'sciezka do skanu zaswiadczenia',
    `created_by`   INT UNSIGNED NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_eqhh_club`        (`club_id`),
    KEY `idx_eqhh_horse`       (`horse_id`),
    KEY `idx_eqhh_event_type`  (`event_type`),
    KEY `idx_eqhh_valid_until` (`valid_until`),
    FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`)              ON DELETE CASCADE,
    FOREIGN KEY (`horse_id`)   REFERENCES `equestrian_horses`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)              ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Status zdrowotny konia (vet, szczepienia, kontuzje, zabiegi)';

-- 2. Dziennik treningowy konia
CREATE TABLE IF NOT EXISTS `equestrian_horse_training` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `horse_id`       INT UNSIGNED NOT NULL,
    `member_id`      INT UNSIGNED NULL COMMENT 'kto trenowal (jezdziec/instruktor)',
    `training_date`  DATETIME NOT NULL,
    `duration_min`   SMALLINT UNSIGNED NULL,
    `training_type`  ENUM(
        'lonza',
        'ujezdzenie_w_jezdzcu',
        'skok',
        'cross',
        'spacer',
        'praca_z_ziemi',
        'odpoczynek_aktywny',
        'rehabilitacja',
        'inny'
    ) NOT NULL DEFAULT 'ujezdzenie_w_jezdzcu',
    `intensity`      ENUM('lekka','umiarkowana','intensywna') NOT NULL DEFAULT 'umiarkowana',
    `arena`          VARCHAR(80) NULL COMMENT 'kryta/zewnetrzna/cross-country/inne',
    `notes`          TEXT NULL,
    `behavior`       ENUM('spokojny','nerwowy','niespodzianka','choroba','kuleje','inny') NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_eqht_club`   (`club_id`),
    KEY `idx_eqht_horse`  (`horse_id`),
    KEY `idx_eqht_date`   (`training_date`),
    KEY `idx_eqht_member` (`member_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)              ON DELETE CASCADE,
    FOREIGN KEY (`horse_id`)  REFERENCES `equestrian_horses`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Dziennik treningowy konia';

SET foreign_key_checks = 1;
