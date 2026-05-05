-- Equestrian Q.1: owners as entities + extra horse data
--
-- Dotychczas equestrian_horses.owner_name byl pojedynczym VARCHAR-em.
-- W praktyce konie maja swoich wlascicieli (czasem zewnetrznych, czasem
-- innych zawodnikow klubu, czasem stowarzyszenia). Wprowadzamy osobna
-- tabele owners + FK z zachowaniem backward-compat (owner_name zostaje
-- jako legacy fallback gdy owner_id IS NULL).
--
-- Plus rozszerzamy equestrian_horses o kolumny wymagane przez PZJ/FEI:
--   - microchip (15-cyfrowy ISO)
--   - fei_passport_no (paszport FEI dla startow miedzynarodowych)
--   - height_cm (wysokosc w klebie)
--   - sport_class (poziom konia: niskie/srednie/wysokie/pol-prof/prof)

SET foreign_key_checks = 0;

-- 1. Tabela wlascicieli koni
CREATE TABLE IF NOT EXISTS `equestrian_horse_owners` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`    INT UNSIGNED NOT NULL,
    `full_name`  VARCHAR(150) NOT NULL,
    `member_id`  INT UNSIGNED NULL COMMENT 'jesli wlasciciel jest tez zawodnikiem klubu',
    `address`    VARCHAR(255) NULL,
    `city`       VARCHAR(100) NULL,
    `phone`      VARCHAR(30)  NULL,
    `email`      VARCHAR(120) NULL,
    `tax_id`     VARCHAR(20)  NULL COMMENT 'NIP/PESEL dla rozliczen',
    `notes`      TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_eqo_club`   (`club_id`),
    KEY `idx_eqo_member` (`member_id`),
    FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wlasciciele koni jezdzieckich (PZJ requirement)';

-- 2. Rozszerzenie equestrian_horses
ALTER TABLE `equestrian_horses`
    ADD COLUMN `owner_id`         INT UNSIGNED NULL AFTER `owner_name`,
    ADD COLUMN `microchip`         VARCHAR(20)  NULL COMMENT '15-cyfrowy ISO 11784/11785' AFTER `owner_id`,
    ADD COLUMN `fei_passport_no`   VARCHAR(40)  NULL COMMENT 'paszport FEI dla startow miedzynarodowych' AFTER `microchip`,
    ADD COLUMN `pzj_passport_no`   VARCHAR(40)  NULL COMMENT 'paszport PZJ' AFTER `fei_passport_no`,
    ADD COLUMN `height_cm`         SMALLINT UNSIGNED NULL COMMENT 'wysokosc w klebie' AFTER `pzj_passport_no`,
    ADD COLUMN `sport_class`       ENUM('rekreacja','sportowa_niska','sportowa_srednia','sportowa_wysoka','pol_profesjonalna','profesjonalna') NULL DEFAULT 'rekreacja' AFTER `height_cm`,
    ADD COLUMN `discipline_focus`  SET('dressage','jumping','eventing','endurance','reining','vaulting','driving','para') NULL COMMENT 'glowne dyscypliny w ktorych kon startuje' AFTER `sport_class`;

ALTER TABLE `equestrian_horses`
    ADD KEY `idx_eqh_owner`     (`owner_id`),
    ADD KEY `idx_eqh_microchip` (`microchip`),
    ADD CONSTRAINT `fk_eqh_owner` FOREIGN KEY (`owner_id`) REFERENCES `equestrian_horse_owners`(`id`) ON DELETE SET NULL;

SET foreign_key_checks = 1;
