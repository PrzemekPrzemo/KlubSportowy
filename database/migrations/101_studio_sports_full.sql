-- 101_studio_sports_full.sql
-- ============================================================
-- Studio sports FULL: joga / fitness / pilates.
--
-- Wszystkie 3 sporty dziela ten sam model biznesowy:
--   - Zajecia grupowe (klasy) wg cyklicznego tygodniowego harmonogramu
--   - Karnety wielokrotnego uzycia (single / multi / unlimited_period)
--   - Zapis na konkretna date (z kontrola pojemnosci + waitlista)
--   - Check-in przez instruktora (mark attended)
--
-- Tabele sa wspolne (sport_key dyskryminuje), bo logika 1:1 — 3 osobne
-- kopie tabel daly by tylko 3x wiecej zoperacji bez zysku z izolacji.
-- Multi-tenant: kazdy SELECT/UPDATE WHERE club_id = ?.
-- ============================================================

SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- studio_class_schedules — tygodniowy harmonogram klas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `studio_class_schedules` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`             INT UNSIGNED NOT NULL,
    `sport_key`           VARCHAR(50)  NOT NULL COMMENT 'yoga | fitness | pilates',
    `name`                VARCHAR(200) NOT NULL COMMENT 'np. Yoga Vinyasa, HIIT, Pilates Mat',
    `instructor_user_id`  INT UNSIGNED NULL,
    `description`         TEXT NULL,
    `difficulty`          ENUM('beginner','intermediate','advanced','open') DEFAULT 'open',
    `day_of_week`         TINYINT NOT NULL COMMENT '1=Mon .. 7=Sun',
    `time_start`          TIME NOT NULL,
    `duration_min`        INT NOT NULL DEFAULT 60,
    `max_capacity`        INT NOT NULL DEFAULT 15,
    `room`                VARCHAR(100) NULL,
    `active`              TINYINT(1) NOT NULL DEFAULT 1,
    `recurring`           TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_studio_sched_club_sport` (`club_id`, `sport_key`, `active`),
    KEY `idx_studio_sched_dow`        (`club_id`, `day_of_week`, `time_start`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tygodniowy harmonogram klas studio (yoga/fitness/pilates)';

-- ------------------------------------------------------------
-- studio_pass_types — typy karnetow w sprzedazy
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `studio_pass_types` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NOT NULL,
    `sport_key`       VARCHAR(50)  NULL COMMENT 'NULL = dowolny sport studio',
    `code`            VARCHAR(50)  NOT NULL,
    `name`            VARCHAR(200) NOT NULL,
    `type`            ENUM('single','multi_class','unlimited_period') NOT NULL,
    `classes_count`   INT NULL COMMENT 'NULL dla unlimited_period',
    `validity_days`   INT NOT NULL,
    `price_cents`     INT NOT NULL,
    `currency`        CHAR(3) NOT NULL DEFAULT 'PLN',
    `active`          TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`      INT NOT NULL DEFAULT 0,
    UNIQUE KEY `uniq_studio_pass_club_code` (`club_id`, `code`),
    KEY `idx_studio_pass_club_active` (`club_id`, `active`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Typy karnetow studio dostepnych w sprzedazy';

-- ------------------------------------------------------------
-- studio_member_passes — zakupione karnety zawodnikow
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `studio_member_passes` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`            INT UNSIGNED NOT NULL,
    `member_id`          INT UNSIGNED NOT NULL,
    `pass_type_id`       INT UNSIGNED NOT NULL,
    `purchased_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `valid_from`         DATE NOT NULL,
    `valid_until`        DATE NOT NULL,
    `classes_total`      INT NULL,
    `classes_remaining`  INT NULL,
    `status`             ENUM('active','exhausted','expired','refunded') NOT NULL DEFAULT 'active',
    `payment_id`         INT UNSIGNED NULL,
    KEY `idx_studio_mpass_member_status` (`member_id`, `status`),
    KEY `idx_studio_mpass_club_member`   (`club_id`,   `member_id`),
    FOREIGN KEY (`club_id`)      REFERENCES `clubs`(`id`)              ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)    REFERENCES `members`(`id`)            ON DELETE CASCADE,
    FOREIGN KEY (`pass_type_id`) REFERENCES `studio_pass_types`(`id`)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Karnety zakupione przez zawodnika (instancja typu)';

-- ------------------------------------------------------------
-- studio_class_bookings — zapisy na konkretne zajecia (data)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `studio_class_bookings` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`        INT UNSIGNED NOT NULL,
    `schedule_id`    INT UNSIGNED NOT NULL,
    `member_id`      INT UNSIGNED NOT NULL,
    `pass_id`        INT UNSIGNED NULL COMMENT 'pass uzyty do zapisu',
    `class_date`     DATE NOT NULL,
    `status`         ENUM('booked','waitlist','attended','no_show','cancelled') NOT NULL DEFAULT 'booked',
    `booked_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `cancelled_at`   DATETIME NULL,
    `attended_at`    DATETIME NULL,
    UNIQUE KEY `uniq_studio_book_member_date` (`schedule_id`, `member_id`, `class_date`),
    KEY `idx_studio_book_class_status`        (`schedule_id`, `class_date`, `status`),
    KEY `idx_studio_book_club_member`         (`club_id`, `member_id`),
    FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)                   ON DELETE CASCADE,
    FOREIGN KEY (`schedule_id`) REFERENCES `studio_class_schedules`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)                 ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zapisy zawodnikow na konkretne wystapienia klasy (data)';

SET foreign_key_checks = 1;
