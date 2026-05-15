-- 078_booking_system.sql
-- Booking system — rezerwacje zasobów klubu (sala/kort/sprzęt/boisko/tor).
--
-- Nowy generyczny model rezerwacji obok istniejącego `facilities`/`facility_bookings`.
-- Idea: `bookable_resources` to nadrzedna tabela zasobów z konfiguracja
-- (typ, kolor, granulacja czasowa, godziny dostepnosci), a `bookings`
-- to wpisy rezerwacji z conflict-detection na poziomie SELECT.
--
-- Multi-tenant: oba CASCADE FK od clubs.id; modele extends ClubScopedModel.
-- FullCalendar.js renderuje `bookings.start_at/end_at` po stronie klienta.

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `bookable_resources` (
    `id`                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`                INT UNSIGNED NOT NULL,
    `name`                   VARCHAR(120) NOT NULL,
    `type`                   ENUM('room','court','equipment','field','pool_lane','other') NOT NULL DEFAULT 'room',
    `capacity`               INT UNSIGNED NULL COMMENT 'Max liczba osob (sala) lub sztuk (sprzet)',
    `description`            TEXT NULL,
    `location`               VARCHAR(200) NULL,
    `color`                  VARCHAR(7) NULL DEFAULT '#6c757d' COMMENT 'HEX kolor dla kalendarza',
    `icon`                   VARCHAR(40) NULL,
    `sport_key`              VARCHAR(40) NULL COMMENT 'Powiazanie z sport (opcjonalne)',
    `booking_unit_minutes`   SMALLINT UNSIGNED NOT NULL DEFAULT 60 COMMENT 'Granularnosc: 30/60/90/120 min',
    `min_advance_hours`      SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Najwczesniej rezerwowac na X godz wprzod',
    `max_advance_days`       SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    `max_duration_minutes`   SMALLINT UNSIGNED NULL COMMENT 'NULL = bez limitu',
    `requires_approval`      TINYINT(1) NOT NULL DEFAULT 0,
    `is_active`              TINYINT(1) NOT NULL DEFAULT 1,
    `available_from`         TIME NULL COMMENT 'Otwarte od godz (NULL = 00:00)',
    `available_until`        TIME NULL COMMENT 'Otwarte do godz (NULL = 23:59)',
    `available_weekdays`     VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6,7' COMMENT 'Lista dni tygodnia 1=pon...7=ndz',
    `created_at`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_club_active` (`club_id`, `is_active`),
    CONSTRAINT `fk_bookable_resources_club` FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bookings` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`              INT UNSIGNED NOT NULL,
    `resource_id`          INT UNSIGNED NOT NULL,
    `member_id`            INT UNSIGNED NULL COMMENT 'NULL gdy bookuje admin/trener dla grupy',
    `booked_by_user_id`    INT UNSIGNED NULL COMMENT 'Klub admin user (zarzad/trener)',
    `title`                VARCHAR(200) NOT NULL,
    `description`          TEXT NULL,
    `start_at`             DATETIME NOT NULL,
    `end_at`               DATETIME NOT NULL,
    `participants_count`   SMALLINT UNSIGNED NULL,
    `status`               ENUM('pending','confirmed','cancelled','completed','no_show') NOT NULL DEFAULT 'confirmed',
    `cancellation_reason`  VARCHAR(255) NULL,
    `cancelled_at`         DATETIME NULL,
    `recurring_pattern`    VARCHAR(100) NULL COMMENT 'RRULE: FREQ=WEEKLY;BYDAY=MO,WE',
    `recurring_until`      DATE NULL,
    `parent_booking_id`    INT UNSIGNED NULL COMMENT 'Dla recurring: pierwsze w serii',
    `notes`                TEXT NULL,
    `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_resource_time` (`resource_id`, `start_at`, `end_at`),
    KEY `idx_club_date` (`club_id`, `start_at`),
    KEY `idx_member` (`member_id`),
    CONSTRAINT `fk_bookings_club`     FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)              ON DELETE CASCADE,
    CONSTRAINT `fk_bookings_resource` FOREIGN KEY (`resource_id`) REFERENCES `bookable_resources`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bookings_member`   FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)            ON DELETE SET NULL,
    CONSTRAINT `fk_bookings_parent`   FOREIGN KEY (`parent_booking_id`) REFERENCES `bookings`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
