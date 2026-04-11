-- Shooting plugin migration: weapons + ammo
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `weapons` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `category`       ENUM('pistolet','karabin','strzelba','pneumatyczna','inna') NOT NULL DEFAULT 'pistolet',
  `brand`          VARCHAR(80) NULL,
  `model`          VARCHAR(80) NULL,
  `caliber`        VARCHAR(30) NULL,
  `serial_number`  VARCHAR(100) NOT NULL,
  `production_year` YEAR NULL,
  `condition_state` ENUM('nowa','dobra','uzytkowa','do_serwisu','wycofana') NOT NULL DEFAULT 'dobra',
  `purchase_date`  DATE NULL,
  `purchase_price` DECIMAL(10,2) NULL,
  `current_holder_id` INT UNSIGNED NULL COMMENT 'aktualnie wypożyczona do zawodnika',
  `notes`          TEXT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_weapons_serial` (`club_id`, `serial_number`),
  KEY `idx_weapons_club` (`club_id`),
  FOREIGN KEY (`club_id`)           REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`current_holder_id`) REFERENCES `members`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Broń klubowa (ewidencja)';

CREATE TABLE IF NOT EXISTS `weapon_assignments` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `weapon_id`    INT UNSIGNED NOT NULL,
  `member_id`    INT UNSIGNED NOT NULL,
  `issued_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `returned_at`  DATETIME NULL,
  `purpose`      VARCHAR(100) NULL,
  `notes`        TEXT NULL,
  `created_by`   INT UNSIGNED NULL,
  KEY `idx_wa_weapon` (`weapon_id`),
  KEY `idx_wa_member` (`member_id`),
  FOREIGN KEY (`weapon_id`)  REFERENCES `weapons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)  REFERENCES `members`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wypożyczenia broni klubowej';

CREATE TABLE IF NOT EXISTS `ammo_stock` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `caliber`     VARCHAR(30) NOT NULL,
  `type`        VARCHAR(50) NULL COMMENT 'np. FMJ, HP, wadcutter',
  `brand`       VARCHAR(80) NULL,
  `quantity`    INT NOT NULL DEFAULT 0,
  `unit_price`  DECIMAL(10,2) NULL,
  `min_stock`   INT NULL COMMENT 'próg alertu',
  `notes`       TEXT NULL,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_ammo_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stan magazynu amunicji';

CREATE TABLE IF NOT EXISTS `ammo_transactions` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ammo_id`    INT UNSIGNED NOT NULL,
  `member_id`  INT UNSIGNED NULL,
  `direction`  ENUM('przyjecie','wydanie','korekta') NOT NULL,
  `quantity`   INT NOT NULL,
  `notes`      VARCHAR(255) NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_at_ammo` (`ammo_id`),
  FOREIGN KEY (`ammo_id`)    REFERENCES `ammo_stock`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)  REFERENCES `members`(`id`)    ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historia ruchów magazynu amunicji';

SET foreign_key_checks = 1;
