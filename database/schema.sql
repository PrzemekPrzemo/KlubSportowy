-- ============================================================
-- ClubDesk — Multi-Sport Club Management Portal
-- Database Schema (Phase 1)
-- ============================================================
-- Strategy: shared database, shared schema, club_id discriminator.
-- Dictionaries with club_id NULL are global defaults.
-- Sports are first-class and orthogonal to clubs (M:M via club_sports).
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ============================================================
-- 1. CORE TENANCY
-- ============================================================

-- ------------------------------------------------------------
-- clubs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clubs` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(150) NOT NULL,
  `short_name`   VARCHAR(50)  NULL,
  `city`         VARCHAR(80)  NULL,
  `nip`          VARCHAR(15)  NULL,
  `regon`        VARCHAR(14)  NULL,
  `krs`          VARCHAR(15)  NULL,
  `email`        VARCHAR(120) NULL,
  `phone`        VARCHAR(20)  NULL,
  `address`      TEXT         NULL,
  `website`      VARCHAR(150) NULL,
  `founded_year` YEAR         NULL,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kluby sportowe zarejestrowane w systemie';

-- ------------------------------------------------------------
-- club_customization
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `club_customization` (
  `club_id`               INT UNSIGNED  PRIMARY KEY,
  `logo_path`             VARCHAR(255)  NULL,
  `primary_color`         VARCHAR(20)   NOT NULL DEFAULT '#0d6efd',
  `navbar_bg`             VARCHAR(20)   NOT NULL DEFAULT '#212529',
  `accent_color`          VARCHAR(20)   NOT NULL DEFAULT '#198754',
  `custom_css`            TEXT          NULL,
  `custom_css_updated_at` DATETIME      NULL,
  `favicon_path`          VARCHAR(255)  NULL COMMENT 'Sciezka per-klub favicon (PNG/ICO)',
  `email_header_html`     TEXT          NULL COMMENT 'HTML do <header> w emailach (max 5000 chars)',
  `email_from_name`       VARCHAR(120)  NULL COMMENT 'Display name w From: header',
  `sms_sender_id`         VARCHAR(11)   NULL COMMENT 'Alphanum sender 1-11 znakow A-Z, 0-9',
  `subdomain`             VARCHAR(80)   NULL UNIQUE COMMENT 'np. azs-warszawa -> azs-warszawa.system.pl',
  `motto`                 VARCHAR(255)  NULL,
  `updated_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wygląd i branding per-klub';

-- ------------------------------------------------------------
-- club_settings (key-value per club)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `club_settings` (
  `club_id`    INT UNSIGNED NOT NULL,
  `key`        VARCHAR(80)  NOT NULL,
  `value`      TEXT         NULL,
  `label`      VARCHAR(120) NOT NULL DEFAULT '',
  `type`       ENUM('text','number','boolean','json') NOT NULL DEFAULT 'text',
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`club_id`, `key`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ustawienia key-value per klub';

-- ------------------------------------------------------------
-- users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`        VARCHAR(60)  NOT NULL UNIQUE,
  `email`           VARCHAR(120) NOT NULL UNIQUE,
  `password`        VARCHAR(255) NOT NULL,
  `full_name`       VARCHAR(120) NOT NULL,
  `phone`           VARCHAR(20)  NULL,
  `is_super_admin`  TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `last_login`      DATETIME     NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Użytkownicy systemu (administratorzy klubów i super-admin)';

-- ------------------------------------------------------------
-- user_clubs (M:M user <-> club with role)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_clubs` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `club_id`    INT UNSIGNED NOT NULL,
  `role`       ENUM('zarzad','trener','instruktor','sedzia','lekarz','ksiegowy') NOT NULL DEFAULT 'instruktor',
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_club_role` (`user_id`, `club_id`, `role`),
  KEY `idx_user_clubs_club` (`club_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Powiązanie użytkowników z klubami (M:M z rolą)';

-- ============================================================
-- 2. SPORTS & FEDERATIONS (the multi-sport core)
-- ============================================================

-- ------------------------------------------------------------
-- federations — polskie związki sportowe
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `federations` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code`       VARCHAR(20)  NOT NULL UNIQUE COMMENT 'PZPN, PZKosz, PZPS, PZSS...',
  `name`       VARCHAR(200) NOT NULL,
  `country`    CHAR(2)      NOT NULL DEFAULT 'PL',
  `website`    VARCHAR(200) NULL,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Związki sportowe (PZPN, PZKosz, PZSS, itd.)';

-- ------------------------------------------------------------
-- sports — dyscypliny najwyższego poziomu (strzelectwo, piłka nożna...)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sports` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key`             VARCHAR(40)  NOT NULL UNIQUE COMMENT 'football, basketball, shooting...',
  `name`            VARCHAR(100) NOT NULL,
  `federation_id`   INT UNSIGNED NULL,
  `icon`            VARCHAR(50)  NULL COMMENT 'nazwa ikony Bootstrap / FontAwesome',
  `color`           VARCHAR(20)  NOT NULL DEFAULT '#0d6efd',
  `team_sport`      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = sport drużynowy (mecze), 0 = indywidualny (zawody)',
  `module_manifest` JSON         NULL COMMENT 'cache manifestu modułu (features, nav)',
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`federation_id`) REFERENCES `federations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Katalog sportów wspieranych przez platformę';

-- ------------------------------------------------------------
-- club_sports — sekcje sportowe w klubie (M:M)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `club_sports` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `sport_id`   INT UNSIGNED NOT NULL,
  `name`       VARCHAR(150) NULL COMMENT 'niestandardowa nazwa sekcji, np. "Sekcja Młodzieżowa"',
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `started_at` DATE         NULL,
  `federation_club_id` VARCHAR(60) NULL COMMENT 'numer klubu w związku (np. nr PZPN)',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_club_sport` (`club_id`, `sport_id`),
  KEY `idx_club_sports_club` (`club_id`),
  KEY `idx_club_sports_sport` (`sport_id`),
  FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sekcje sportowe w klubie (klub może mieć wiele sportów)';

-- ------------------------------------------------------------
-- disciplines — pod-dyscypliny w ramach sportu
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disciplines` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sport_id`   INT UNSIGNED NOT NULL,
  `club_id`    INT UNSIGNED NULL COMMENT 'NULL = globalna, NOT NULL = per-klub',
  `name`       VARCHAR(100) NOT NULL,
  `short_code` VARCHAR(20)  NOT NULL,
  `description` TEXT        NULL,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_disciplines_sport` (`sport_id`),
  KEY `idx_disciplines_club`  (`club_id`),
  FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Dyscypliny / konkurencje w ramach sportu (np. pistolet, karabin)';

-- ------------------------------------------------------------
-- member_classes — klasy sportowe per-sport
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_classes` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sport_id`   INT UNSIGNED NOT NULL,
  `club_id`    INT UNSIGNED NULL,
  `name`       VARCHAR(60)  NOT NULL,
  `short_code` VARCHAR(10)  NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  KEY `idx_member_classes_sport` (`sport_id`),
  KEY `idx_member_classes_club`  (`club_id`),
  FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Klasy sportowe (Master/A/B/C dla strzelectwa, U-19/senior dla piłki)';

-- ------------------------------------------------------------
-- age_categories — kategorie wiekowe
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `age_categories` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sport_id`   INT UNSIGNED NULL COMMENT 'NULL = uniwersalna',
  `club_id`    INT UNSIGNED NULL,
  `name`       VARCHAR(60)  NOT NULL,
  `age_from`   TINYINT UNSIGNED NOT NULL,
  `age_to`     TINYINT UNSIGNED NOT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  KEY `idx_age_cat_sport` (`sport_id`),
  KEY `idx_age_cat_club`  (`club_id`),
  FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kategorie wiekowe (Junior, Senior, Weteran...)';

-- ============================================================
-- 3. MEMBERS (zawodnicy)
-- ============================================================

-- ------------------------------------------------------------
-- members
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
  `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`            INT UNSIGNED NOT NULL,
  `member_number`      VARCHAR(20)  NOT NULL,
  `card_number`        VARCHAR(30)  NULL COMMENT 'numer karty dostępu',
  `first_name`         VARCHAR(60)  NOT NULL,
  `last_name`          VARCHAR(60)  NOT NULL,
  `pesel`              VARCHAR(11)  NULL,
  `birth_date`         DATE         NULL,
  `gender`             ENUM('M','K') NULL,
  `nationality`        CHAR(2)      NOT NULL DEFAULT 'PL',
  `email`              VARCHAR(120) NULL,
  `phone`              VARCHAR(20)  NULL,
  `address_street`     VARCHAR(150) NULL,
  `address_city`       VARCHAR(80)  NULL,
  `address_postal`     VARCHAR(10)  NULL,
  `photo_path`         VARCHAR(255) NULL,
  `join_date`          DATE         NOT NULL,
  `status`             ENUM('aktywny','zawieszony','wykreslony','urlop') NOT NULL DEFAULT 'aktywny',
  `notes`              TEXT         NULL,
  `portal_password`    VARCHAR(255) NULL COMMENT 'hasło do portalu zawodnika',
  `portal_last_login`  DATETIME     NULL,
  `created_by`         INT UNSIGNED NULL,
  `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_member_club_number` (`club_id`, `member_number`),
  KEY `idx_members_club` (`club_id`),
  KEY `idx_members_status` (`status`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zawodnicy klubu';

-- ------------------------------------------------------------
-- member_sports — w jakich sekcjach trenuje zawodnik
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_sports` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id`       INT UNSIGNED NOT NULL,
  `club_sport_id`   INT UNSIGNED NOT NULL,
  `class_id`        INT UNSIGNED NULL,
  `discipline_id`   INT UNSIGNED NULL,
  `age_category_id` INT UNSIGNED NULL,
  `position`        VARCHAR(50)  NULL COMMENT 'pozycja w sportach drużynowych',
  `jersey_number`   SMALLINT UNSIGNED NULL,
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `joined_at`       DATE         NOT NULL,
  UNIQUE KEY `uq_member_club_sport` (`member_id`, `club_sport_id`),
  KEY `idx_ms_club_sport` (`club_sport_id`),
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)        ON DELETE CASCADE,
  FOREIGN KEY (`club_sport_id`) REFERENCES `club_sports`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`class_id`)      REFERENCES `member_classes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`)    ON DELETE SET NULL,
  FOREIGN KEY (`age_category_id`) REFERENCES `age_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Przypisanie zawodnika do sekcji sportowej klubu';

-- ------------------------------------------------------------
-- member_medical_exams — badania sportowe (generyczne)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_medical_exams` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `member_id`      INT UNSIGNED NOT NULL,
  `exam_type`      VARCHAR(80)  NOT NULL DEFAULT 'ogólne badanie sportowe',
  `exam_date`      DATE         NOT NULL,
  `valid_until`    DATE         NOT NULL,
  `doctor_name`    VARCHAR(120) NULL,
  `notes`          TEXT         NULL,
  `document_path`  VARCHAR(255) NULL,
  `created_by`     INT UNSIGNED NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_mme_club` (`club_id`),
  KEY `idx_mme_member` (`member_id`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)  REFERENCES `members`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Badania lekarskie sportowców';

-- ------------------------------------------------------------
-- member_licenses — licencje federacji (per-sport, per-zawodnik)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_licenses` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `member_id`       INT UNSIGNED NOT NULL,
  `sport_id`        INT UNSIGNED NOT NULL,
  `federation_id`   INT UNSIGNED NULL,
  `license_type`    ENUM('zawodnicza','trenerska','sedziowska','klubowa','patent') NOT NULL DEFAULT 'zawodnicza',
  `license_number`  VARCHAR(80)  NOT NULL,
  `issue_date`      DATE         NOT NULL,
  `valid_until`     DATE         NOT NULL,
  `qr_code`         VARCHAR(255) NULL,
  `status`          ENUM('aktywna','wygasla','zawieszona') NOT NULL DEFAULT 'aktywna',
  `notes`           TEXT         NULL,
  `created_by`      INT UNSIGNED NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_ml_club` (`club_id`),
  KEY `idx_ml_member` (`member_id`),
  KEY `idx_ml_sport` (`sport_id`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)      ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`)      REFERENCES `sports`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`federation_id`) REFERENCES `federations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Licencje związkowe zawodników (generyczne dla wszystkich federacji)';

-- ============================================================
-- 4. FINANCES (składki, opłaty, płatności)
-- ============================================================

-- ------------------------------------------------------------
-- fee_rates — stawki opłat (szablony)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fee_rates` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `sport_id`        INT UNSIGNED NULL COMMENT 'NULL = stawka ogólnoklubowa',
  `class_id`        INT UNSIGNED NULL COMMENT 'stawka dla konkretnej klasy',
  `name`            VARCHAR(120) NOT NULL,
  `amount`          DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `period`          ENUM('monthly','quarterly','yearly','one_time') NOT NULL DEFAULT 'monthly',
  `fee_type`        ENUM('skladka','wpisowe','licencja','zawody','obóz','inne') NOT NULL DEFAULT 'skladka',
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `description`     TEXT         NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_fee_rates_club`  (`club_id`),
  KEY `idx_fee_rates_sport` (`sport_id`),
  FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`)         ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`)        ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `member_classes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Szablony stawek opłat/składek';

-- ------------------------------------------------------------
-- payments — zarejestrowane wpłaty
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`       INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `fee_rate_id`   INT UNSIGNED NULL,
  `sport_id`      INT UNSIGNED NULL,
  `amount`        DECIMAL(10,2) NOT NULL,
  `payment_date`  DATE NOT NULL,
  `period_year`   YEAR NOT NULL,
  `period_month`  TINYINT UNSIGNED NULL COMMENT 'NULL = roczna',
  `method`        ENUM('gotowka','przelew','karta','blik','inny') NOT NULL DEFAULT 'przelew',
  `reference`     VARCHAR(100) NULL,
  `notes`         TEXT NULL,
  `created_by`    INT UNSIGNED NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_payments_club`   (`club_id`),
  KEY `idx_payments_member` (`member_id`),
  KEY `idx_payments_period` (`period_year`, `period_month`),
  FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`fee_rate_id`) REFERENCES `fee_rates`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sport_id`)    REFERENCES `sports`(`id`)    ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zarejestrowane płatności zawodników';

-- ============================================================
-- 5. EVENTS (generic: match OR competition OR training)
-- ============================================================

CREATE TABLE IF NOT EXISTS `teams` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `sport_id`    INT UNSIGNED NOT NULL,
  `name`        VARCHAR(120) NOT NULL,
  `age_category_id` INT UNSIGNED NULL,
  `is_own_team` TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1 = drużyna klubowa, 0 = drużyna przeciwnika',
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_teams_club`  (`club_id`),
  KEY `idx_teams_sport` (`sport_id`),
  FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`age_category_id`) REFERENCES `age_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Drużyny klubowe i drużyny przeciwnika';

CREATE TABLE IF NOT EXISTS `events` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `sport_id`        INT UNSIGNED NULL,
  `discipline_id`   INT UNSIGNED NULL,
  `type`            ENUM('mecz','zawody','trening','obóz','turniej','inny') NOT NULL DEFAULT 'zawody',
  `name`            VARCHAR(200) NOT NULL,
  `event_date`      DATETIME NOT NULL,
  `end_date`        DATETIME NULL,
  `location`        VARCHAR(200) NULL,
  `home_team_id`    INT UNSIGNED NULL,
  `away_team_id`    INT UNSIGNED NULL,
  `home_score`      SMALLINT UNSIGNED NULL,
  `away_score`      SMALLINT UNSIGNED NULL,
  `status`          ENUM('planowane','otwarte','w_trakcie','zakonczone','odwolane') NOT NULL DEFAULT 'planowane',
  `max_entries`     SMALLINT UNSIGNED NULL,
  `description`     TEXT NULL,
  `created_by`      INT UNSIGNED NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_events_club`  (`club_id`),
  KEY `idx_events_sport` (`sport_id`),
  KEY `idx_events_date`  (`event_date`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`)      REFERENCES `sports`(`id`)      ON DELETE SET NULL,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`home_team_id`)  REFERENCES `teams`(`id`)       ON DELETE SET NULL,
  FOREIGN KEY (`away_team_id`)  REFERENCES `teams`(`id`)       ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wydarzenia sportowe (generyczne: mecz, zawody, trening, turniej)';

CREATE TABLE IF NOT EXISTS `event_entries` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_id`      INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `team_id`       INT UNSIGNED NULL,
  `class_id`      INT UNSIGNED NULL,
  `status`        ENUM('zgloszony','potwierdzony','wycofany','dyskwalifikowany') NOT NULL DEFAULT 'zgloszony',
  `registered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registered_by` INT UNSIGNED NULL,
  UNIQUE KEY `uq_entry` (`event_id`, `member_id`),
  FOREIGN KEY (`event_id`)      REFERENCES `events`(`id`)        ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`team_id`)       REFERENCES `teams`(`id`)         ON DELETE SET NULL,
  FOREIGN KEY (`class_id`)      REFERENCES `member_classes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`registered_by`) REFERENCES `users`(`id`)         ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zgłoszenia do wydarzeń / składy meczowe';

CREATE TABLE IF NOT EXISTS `event_results` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_id`   INT UNSIGNED NOT NULL,
  `member_id`  INT UNSIGNED NULL,
  `team_id`    INT UNSIGNED NULL,
  `score`      DECIMAL(10,2) NULL,
  `place`      SMALLINT UNSIGNED NULL,
  `extra`      JSON NULL COMMENT 'sport-specific payload (np. 10 strzał dla strzelectwa, assisty/zbiórki dla koszykówki)',
  `notes`      TEXT NULL,
  `entered_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_event_results_event` (`event_id`),
  FOREIGN KEY (`event_id`)   REFERENCES `events`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)  REFERENCES `members`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team_id`)    REFERENCES `teams`(`id`)   ON DELETE SET NULL,
  FOREIGN KEY (`entered_by`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wyniki wydarzeń — score + JSON extra dla specyfiki sportu';


-- ============================================================
-- 6. SAAS PLATFORM (subscriptions, billing, logs, settings)
-- ============================================================

CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code`          VARCHAR(40) NOT NULL UNIQUE,
  `name`          VARCHAR(100) NOT NULL,
  `max_members`   INT UNSIGNED NULL COMMENT 'NULL = bez limitu',
  `max_sports`    TINYINT UNSIGNED NULL COMMENT 'NULL = bez limitu sekcji',
  `price_monthly` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `price_yearly`  DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `features`      JSON NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Plany subskrypcyjne SaaS';

CREATE TABLE IF NOT EXISTS `club_subscriptions` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL UNIQUE,
  `plan_id`     INT UNSIGNED NOT NULL,
  `valid_until` DATE NOT NULL,
  `status`      ENUM('trial','active','expired','cancelled','suspended') NOT NULL DEFAULT 'trial',
  `billing_cycle` ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Aktywna subskrypcja klubu';

CREATE TABLE IF NOT EXISTS `billing_invoices` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `number`     VARCHAR(40) NOT NULL,
  `issue_date` DATE NOT NULL,
  `due_date`   DATE NOT NULL,
  `total`      DECIMAL(10,2) NOT NULL,
  `status`     ENUM('draft','issued','paid','cancelled') NOT NULL DEFAULT 'draft',
  `paid_at`    DATETIME NULL,
  `notes`      TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_billing_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Faktury za subskrypcję SaaS';

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NULL,
  `user_id`    INT UNSIGNED NULL,
  `action`     VARCHAR(80) NOT NULL,
  `entity`     VARCHAR(60) NULL,
  `entity_id`  INT UNSIGNED NULL,
  `details`    TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_activity_club` (`club_id`),
  KEY `idx_activity_user` (`user_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log audytowy aktywności w systemie';

CREATE TABLE IF NOT EXISTS `settings` (
  `key`        VARCHAR(80)  NOT NULL PRIMARY KEY,
  `value`      TEXT         NULL,
  `label`      VARCHAR(120) NOT NULL,
  `type`       ENUM('text','number','boolean','json') NOT NULL DEFAULT 'text',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Globalne ustawienia systemu';

SET foreign_key_checks = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Federations (polskie związki sportowe)
INSERT INTO `federations` (`code`, `name`, `website`) VALUES
  ('PZPN',    'Polski Związek Piłki Nożnej',          'https://www.pzpn.pl'),
  ('PZKosz',  'Polski Związek Koszykówki',            'https://www.pzkosz.pl'),
  ('PZPS',    'Polski Związek Piłki Siatkowej',       'https://www.pzps.pl'),
  ('PZSS',    'Polski Związek Strzelectwa Sportowego','https://www.pzss.org.pl'),
  ('PZLA',    'Polski Związek Lekkiej Atletyki',      'https://www.pzla.pl'),
  ('PZHL',    'Polski Związek Hokeja na Lodzie',      'https://www.pzhl.org.pl'),
  ('PZPR',    'Polski Związek Piłki Ręcznej',         'https://www.zprp.pl'),
  ('PZT',     'Polski Związek Tenisowy',              'https://www.pzt.pl'),
  ('PZP',     'Polski Związek Pływacki',              'https://www.polswim.pl'),
  ('PZW',     'Polski Związek Wrotkarski',            'https://www.pzw.org.pl'),
  ('PZJ',     'Polski Związek Judo',                  'https://www.pzjudo.pl'),
  ('PZKarate','Polski Związek Karate',                'https://www.pzkarate.pl');

-- Sports catalog
INSERT INTO `sports` (`key`, `name`, `federation_id`, `icon`, `color`, `team_sport`, `sort_order`) VALUES
  ('football',      'Piłka nożna',       1,  'bi-dribbble',      '#28a745', 1, 10),
  ('basketball',    'Koszykówka',        2,  'bi-record-circle', '#fd7e14', 1, 20),
  ('volleyball',    'Siatkówka',         3,  'bi-circle',        '#ffc107', 1, 30),
  ('athletics',     'Lekka atletyka',    5,  'bi-stopwatch',     '#0dcaf0', 0, 50),
  ('icehockey',     'Hokej na lodzie',   6,  'bi-snow',          '#0d6efd', 1, 60),
  ('handball',      'Piłka ręczna',      7,  'bi-hand-index',    '#6f42c1', 1, 70),
  ('tennis',        'Tenis',             8,  'bi-cursor',        '#20c997', 0, 80),
  ('swimming',      'Pływanie',          9,  'bi-water',         '#0d6efd', 0, 90),
  ('rollerskating', 'Wrotkarstwo',      10, 'bi-shuffle',       '#e83e8c', 0, 100),
  ('judo',          'Judo',             11, 'bi-shield',        '#343a40', 0, 110),
  ('karate',        'Karate',           12, 'bi-shield-shaded', '#6c757d', 0, 120);

-- Sport-specific disciplines (globalne — club_id NULL)

-- Piłka nożna
INSERT INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='football'),   '11-osobowa', 'PN11'),
  ((SELECT id FROM sports WHERE `key`='football'),   'Futsal',     'FSAL'),
  ((SELECT id FROM sports WHERE `key`='football'),   'Plażowa',    'BSOC');

-- Koszykówka
INSERT INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='basketball'), '5x5',  'B5X5'),
  ((SELECT id FROM sports WHERE `key`='basketball'), '3x3',  'B3X3');

-- Siatkówka
INSERT INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='volleyball'), 'Halowa',   'VHAL'),
  ((SELECT id FROM sports WHERE `key`='volleyball'), 'Plażowa',  'VPLA');

-- Strzelectwo: obsługiwane przez zewnętrzny system shootero.pl,
-- nie seedujemy w ClubDesk.

-- Lekka atletyka
INSERT INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='athletics'), 'Sprint 100m',  'S100'),
  ((SELECT id FROM sports WHERE `key`='athletics'), 'Sprint 200m',  'S200'),
  ((SELECT id FROM sports WHERE `key`='athletics'), 'Bieg 800m',    'R800'),
  ((SELECT id FROM sports WHERE `key`='athletics'), 'Maraton',      'MAR'),
  ((SELECT id FROM sports WHERE `key`='athletics'), 'Skok w dal',   'LJMP');

-- Wrotkarstwo
INSERT INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='rollerskating'), 'Speed skating',  'SPEED'),
  ((SELECT id FROM sports WHERE `key`='rollerskating'), 'Freestyle',      'FREE'),
  ((SELECT id FROM sports WHERE `key`='rollerskating'), 'Inline hockey',  'IHOC'),
  ((SELECT id FROM sports WHERE `key`='rollerskating'), 'Roller derby',   'DERB');

-- Member classes per sport
-- (klasy strzeleckie usunięte — strzelectwo obsługiwane przez shootero.pl)

INSERT INTO `member_classes` (`sport_id`, `name`, `short_code`, `sort_order`) VALUES
  ((SELECT id FROM sports WHERE `key`='football'), 'Senior',      'SR', 1),
  ((SELECT id FROM sports WHERE `key`='football'), 'Junior',      'JR', 2),
  ((SELECT id FROM sports WHERE `key`='football'), 'Junior mł.',  'JM', 3),
  ((SELECT id FROM sports WHERE `key`='football'), 'Trampkarz',   'TR', 4),
  ((SELECT id FROM sports WHERE `key`='football'), 'Młodzik',     'ML', 5),
  ((SELECT id FROM sports WHERE `key`='football'), 'Orlik',       'OR', 6),
  ((SELECT id FROM sports WHERE `key`='football'), 'Żak',         'ZA', 7),
  ((SELECT id FROM sports WHERE `key`='football'), 'Skrzat',      'SK', 8);

INSERT INTO `member_classes` (`sport_id`, `name`, `short_code`, `sort_order`) VALUES
  ((SELECT id FROM sports WHERE `key`='basketball'), 'Senior',  'SR', 1),
  ((SELECT id FROM sports WHERE `key`='basketball'), 'U-20',    'U20', 2),
  ((SELECT id FROM sports WHERE `key`='basketball'), 'U-18',    'U18', 3),
  ((SELECT id FROM sports WHERE `key`='basketball'), 'U-16',    'U16', 4),
  ((SELECT id FROM sports WHERE `key`='basketball'), 'U-14',    'U14', 5),
  ((SELECT id FROM sports WHERE `key`='basketball'), 'U-12',    'U12', 6);

-- Uniwersalne kategorie wiekowe (sport_id NULL)
INSERT INTO `age_categories` (`name`, `age_from`, `age_to`, `sort_order`) VALUES
  ('Dzieci (U-8)',     5,  8,   1),
  ('Skrzat (U-10)',    9,  10,  2),
  ('Żak (U-12)',       11, 12,  3),
  ('Orlik (U-14)',     13, 14,  4),
  ('Młodzik (U-16)',   15, 16,  5),
  ('Trampkarz (U-18)', 17, 18,  6),
  ('Junior (U-20)',    19, 20,  7),
  ('Senior',           21, 34,  8),
  ('Weteran 35+',      35, 44,  9),
  ('Weteran 45+',      45, 54, 10),
  ('Weteran 55+',      55, 99, 11);

-- Subscription plans (baseline)
-- Q.1: aktualne plany Q.1 (trial_v2/starter/club/multi_sport/enterprise/federation)
-- są wprowadzane przez migrację 052_pricing_overhaul.sql. Tu zachowujemy
-- legacy seed (trial/basic/standard/premium) jako bezpieczny minimal — ASCII-only
-- aby uniknąć problemów z encoding na różnych konfiguracjach mysql client.
INSERT INTO `subscription_plans` (`code`, `name`, `max_members`, `max_sports`, `price_monthly`, `price_yearly`, `features`, `sort_order`) VALUES
  ('trial',    'Trial (30 dni)', 25,   1,    0.00,    0.00,
     '{"sms":false,"api":false,"backup":false,"custom_css":false}', 1),
  ('basic',    'Basic',          100,  2,   49.00,  490.00,
     '{"sms":false,"api":false,"backup":true,"custom_css":false}',  2),
  ('standard', 'Standard',       500,  5,  149.00, 1490.00,
     '{"sms":true,"api":true,"backup":true,"custom_css":true}',     3),
  ('premium',  'Premium',        NULL, NULL, 299.00, 2990.00,
     '{"sms":true,"api":true,"backup":true,"custom_css":true,"white_label":true}', 4)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Global settings
INSERT INTO `settings` (`key`, `value`, `label`, `type`) VALUES
  ('base_domain',         '',          'Domena bazowa systemu (np. clubdesk.pl)', 'text'),
  ('system_name',         'ClubDesk', 'Nazwa platformy',                            'text'),
  ('system_logo',         '',          'Ścieżka do logo systemu',                      'text'),
  ('alert_payment_days',  '30',        'Alert zaległości w składkach (dni)',           'number'),
  ('alert_license_days',  '60',        'Alert wygasającej licencji (dni)',             'number'),
  ('alert_medical_days',  '30',        'Alert wygasających badań (dni)',               'number'),
  ('trial_length_days',   '30',        'Długość okresu próbnego (dni)',                'number'),
  ('allow_public_registration', '1',   'Zezwól klubom na rejestrację publiczną',       'boolean');

-- Domyślny super-admin (hasło: Admin1234! — zmień po pierwszym logowaniu)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `is_super_admin`) VALUES
  ('admin', 'admin@clubdesk.pl',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Administrator Systemu', 1);

-- Domyślny klub demo
INSERT INTO `clubs` (`name`, `short_name`, `city`, `email`) VALUES
  ('Klub Demo', 'DEMO', 'Warszawa', 'demo@clubdesk.pl');

INSERT INTO `club_customization` (`club_id`) VALUES (1);

INSERT INTO `club_subscriptions` (`club_id`, `plan_id`, `valid_until`, `status`, `billing_cycle`) VALUES
  (1, (SELECT id FROM subscription_plans WHERE code='trial'),
      DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'trial', 'monthly');

INSERT INTO `user_clubs` (`user_id`, `club_id`, `role`) VALUES
  (1, 1, 'zarzad');
-- Migration: trainings (Phase 2.1)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `trainings` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `sport_id`        INT UNSIGNED NULL,
  `club_sport_id`   INT UNSIGNED NULL,
  `name`            VARCHAR(150) NOT NULL,
  `description`     TEXT NULL,
  `location`        VARCHAR(150) NULL,
  `start_time`      DATETIME NOT NULL,
  `end_time`        DATETIME NULL,
  `max_participants` SMALLINT UNSIGNED NULL,
  `instructor_id`   INT UNSIGNED NULL COMMENT 'user-instruktor',
  `status`          ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') NOT NULL DEFAULT 'zaplanowany',
  `created_by`      INT UNSIGNED NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_trainings_club` (`club_id`),
  KEY `idx_trainings_time` (`start_time`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`)      REFERENCES `sports`(`id`)      ON DELETE SET NULL,
  FOREIGN KEY (`club_sport_id`) REFERENCES `club_sports`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`)       ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zajęcia treningowe klubu';

CREATE TABLE IF NOT EXISTS `training_attendees` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `training_id`  INT UNSIGNED NOT NULL,
  `member_id`    INT UNSIGNED NOT NULL,
  `status`       ENUM('zapisany','obecny','nieobecny','spozniony','wypisany') NOT NULL DEFAULT 'zapisany',
  `registered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes`        VARCHAR(255) NULL,
  UNIQUE KEY `uq_training_member` (`training_id`, `member_id`),
  KEY `idx_ta_member` (`member_id`),
  FOREIGN KEY (`training_id`) REFERENCES `trainings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Obecność zawodników na treningach';

SET foreign_key_checks = 1;
-- Migration: calendar (Phase 2.1b)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `calendar_event_categories` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `name`       VARCHAR(80)  NOT NULL,
  `color`      VARCHAR(20)  NOT NULL DEFAULT '#0d6efd',
  `icon`       VARCHAR(50)  NULL,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_cec_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kategorie wydarzeń w kalendarzu (np. mecz ligowy, trening, obóz)';

CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NULL,
  `sport_id`    INT UNSIGNED NULL,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `location`    VARCHAR(200) NULL,
  `start_at`    DATETIME NOT NULL,
  `end_at`      DATETIME NULL,
  `all_day`     TINYINT(1)  NOT NULL DEFAULT 0,
  `visibility`  ENUM('private','club','public') NOT NULL DEFAULT 'club',
  `link_type`   ENUM('none','training','event','match') NOT NULL DEFAULT 'none',
  `link_id`     INT UNSIGNED NULL COMMENT 'ID w odpowiedniej tabeli (training/event)',
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_calev_club`  (`club_id`),
  KEY `idx_calev_start` (`start_at`),
  FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)                   ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `calendar_event_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sport_id`)    REFERENCES `sports`(`id`)                  ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`)                   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wpisy kalendarza klubu (generyczne — łączone z treningami/wydarzeniami przez link_type + link_id)';

SET foreign_key_checks = 1;
-- Migration: announcements (Phase 2.3)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `announcements` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `sport_id`    INT UNSIGNED NULL COMMENT 'NULL = ogólnoklubowe',
  `title`       VARCHAR(200) NOT NULL,
  `content`     TEXT NOT NULL,
  `priority`    ENUM('normal','important','urgent') NOT NULL DEFAULT 'normal',
  `target`      ENUM('staff','members','all','public') NOT NULL DEFAULT 'members',
  `published`   TINYINT(1) NOT NULL DEFAULT 1,
  `publish_from` DATETIME NULL,
  `publish_to`   DATETIME NULL,
  `author_id`   INT UNSIGNED NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_ann_club` (`club_id`),
  KEY `idx_ann_priority` (`priority`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`)  REFERENCES `sports`(`id`)  ON DELETE SET NULL,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ogłoszenia klubowe (widoczne dla zarządu/zawodników/publiczne)';

SET foreign_key_checks = 1;
-- Migration: role permissions matrix (Phase 2.3b)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`   INT UNSIGNED NULL COMMENT 'NULL = globalny domyślny',
  `role`      VARCHAR(40) NOT NULL,
  `module`    VARCHAR(40) NOT NULL,
  `can_view`  TINYINT(1)  NOT NULL DEFAULT 0,
  `can_edit`  TINYINT(1)  NOT NULL DEFAULT 0,
  `updated_at` DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_rp` (`club_id`, `role`, `module`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Macierz uprawnień rola x modul (NULL club_id = domyślne)';

-- Seed domyślnych ról (globalnych — club_id NULL)
-- zarzad ma pełen dostęp do wszystkich modułów (RBAC defaults)
INSERT IGNORE INTO role_permissions (club_id, role, module, can_view, can_edit) VALUES
  -- ZARZAD: pełne uprawnienia
  (NULL, 'zarzad', 'members',       1, 1),
  (NULL, 'zarzad', 'sports',        1, 1),
  (NULL, 'zarzad', 'calendar',      1, 1),
  (NULL, 'zarzad', 'events',        1, 1),
  (NULL, 'zarzad', 'trainings',     1, 1),
  (NULL, 'zarzad', 'fees',          1, 1),
  (NULL, 'zarzad', 'medical',       1, 1),
  (NULL, 'zarzad', 'announcements', 1, 1),
  (NULL, 'zarzad', 'gallery',       1, 1),
  (NULL, 'zarzad', 'messages',      1, 1),
  (NULL, 'zarzad', 'analytics',     1, 1),
  (NULL, 'zarzad', 'bookings',      1, 1),
  (NULL, 'zarzad', 'reports',       1, 1),
  (NULL, 'zarzad', 'club',          1, 1),
  (NULL, 'zarzad', 'shop',          1, 1),
  (NULL, 'zarzad', 'livestream',    1, 1),

  -- TRENER: zawodnicy + sekcje read, kalendarz/treningi RW
  (NULL, 'trener', 'members',       1, 1),
  (NULL, 'trener', 'sports',        1, 0),
  (NULL, 'trener', 'calendar',      1, 1),
  (NULL, 'trener', 'events',        1, 1),
  (NULL, 'trener', 'trainings',     1, 1),
  (NULL, 'trener', 'medical',       1, 0),
  (NULL, 'trener', 'announcements', 1, 1),
  (NULL, 'trener', 'gallery',       1, 1),
  (NULL, 'trener', 'messages',      1, 1),
  (NULL, 'trener', 'reports',       1, 0),
  (NULL, 'trener', 'fees',          1, 0),

  -- INSTRUKTOR: jak trener, mniej edycji
  (NULL, 'instruktor', 'members',   1, 0),
  (NULL, 'instruktor', 'sports',    1, 0),
  (NULL, 'instruktor', 'calendar',  1, 1),
  (NULL, 'instruktor', 'events',    1, 0),
  (NULL, 'instruktor', 'trainings', 1, 1),
  (NULL, 'instruktor', 'announcements', 1, 0),
  (NULL, 'instruktor', 'gallery',   1, 1),
  (NULL, 'instruktor', 'messages',  1, 1),

  -- SEDZIA
  (NULL, 'sedzia', 'events',        1, 1),
  (NULL, 'sedzia', 'calendar',      1, 0),

  -- LEKARZ: medyczne (RW) + zawodnicy (read)
  (NULL, 'lekarz', 'members',       1, 0),
  (NULL, 'lekarz', 'medical',       1, 1),

  -- KSIEGOWY: opłaty (RW) + zawodnicy (read) + raporty
  (NULL, 'ksiegowy', 'members',     1, 0),
  (NULL, 'ksiegowy', 'fees',        1, 1),
  (NULL, 'ksiegowy', 'reports',     1, 1);

SET foreign_key_checks = 1;
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
-- Shooting plugin migration: judges (sędziowie PZSS)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `judge_licenses` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `member_id`      INT UNSIGNED NOT NULL,
  `class`          ENUM('III','II','I','P') NOT NULL DEFAULT 'III' COMMENT 'PZSS: III, II, I lub Państwowa',
  `license_number` VARCHAR(60) NOT NULL,
  `disciplines`    VARCHAR(255) NULL COMMENT 'CSV listy dyscyplin (PS, KS, TR...)',
  `issue_date`     DATE NOT NULL,
  `valid_until`    DATE NOT NULL,
  `status`         ENUM('aktywna','wygasla','zawieszona') NOT NULL DEFAULT 'aktywna',
  `fee_paid`       DECIMAL(10,2) NULL,
  `notes`          TEXT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_jl_club`   (`club_id`),
  KEY `idx_jl_member` (`member_id`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Licencje sędziowskie PZSS';

SET foreign_key_checks = 1;
-- Migration: email queue + templates (Phase 4.1)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`       INT UNSIGNED NULL COMMENT 'NULL = globalny szablon domyślny',
  `template_type` VARCHAR(80) NOT NULL,
  `name`          VARCHAR(120) NOT NULL,
  `subject`       VARCHAR(255) NOT NULL,
  `body`          TEXT NOT NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_email_tpl` (`club_id`, `template_type`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Szablony e-mail — globalne domyślne lub nadpisane per-klub';

CREATE TABLE IF NOT EXISTS `email_queue` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `to_email`    VARCHAR(120) NOT NULL,
  `to_name`     VARCHAR(120) NULL,
  `subject`     VARCHAR(255) NOT NULL,
  `body`        TEXT NOT NULL,
  `template_type` VARCHAR(80) NULL,
  `status`      ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `error`       TEXT NULL,
  `scheduled_at` DATETIME NULL,
  `sent_at`     DATETIME NULL,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_eq_status` (`status`, `scheduled_at`),
  KEY `idx_eq_club`   (`club_id`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kolejka wiadomości e-mail do wysłania';

-- Domyślne szablony globalne (club_id NULL)
INSERT IGNORE INTO email_templates (club_id, template_type, name, subject, body) VALUES
  (NULL, 'welcome',         'Powitanie nowego zawodnika',
   'Witaj w klubie {club_name}!',
   'Cześć {first_name},\n\nWitaj w klubie {club_name}! Twój numer członkowski to {member_number}.\n\nPozdrawiamy,\nZarząd klubu {club_name}'),

  (NULL, 'fee_reminder',    'Przypomnienie o składce',
   'Przypomnienie o składce — {club_name}',
   'Cześć {first_name},\n\nPrzypominamy o zaległej składce w klubie {club_name}.\nKwota: {amount} zł.\n\nProsimy o uregulowanie w ciągu 14 dni.\n\nPozdrawiamy,\nZarząd'),

  (NULL, 'license_expiry',  'Licencja wkrótce wygasa',
   'Twoja licencja {license_type} wygasa za {days} dni',
   'Cześć {first_name},\n\nTwoja licencja {license_type} (numer: {license_number}) wygasa {valid_until}.\nPozostało {days} dni. Prosimy o odnowienie w terminie.\n\nPozdrawiamy,\nKlub {club_name}'),

  (NULL, 'medical_expiry',  'Badanie lekarskie wygasa',
   'Twoje badanie lekarskie wygasa za {days} dni',
   'Cześć {first_name},\n\nTwoje badanie lekarskie wygasa {valid_until}.\nPozostało {days} dni. Prosimy o wykonanie nowego badania.\n\nPozdrawiamy,\nKlub {club_name}'),

  (NULL, 'event_reminder',  'Przypomnienie o wydarzeniu',
   'Przypomnienie: {event_name} — {event_date}',
   'Cześć {first_name},\n\nPrzypominamy o zbliżającym się wydarzeniu:\n{event_name} — {event_date}, {event_location}\n\nPozdrawiamy,\nKlub {club_name}'),

  (NULL, 'password_reset',  'Reset hasła',
   'Reset hasła w portalu {club_name}',
   'Cześć {first_name},\n\nWygenerowano link do resetu hasła. Kliknij poniższy link w ciągu 1 godziny:\n\n{reset_link}\n\nJeśli nie prosiłeś/aś o reset, zignoruj tę wiadomość.\n\nPozdrawiamy,\nKlub {club_name}');

SET foreign_key_checks = 1;
-- Migration: SMS queue (Phase 4.2)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `sms_queue` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `to_phone`   VARCHAR(20) NOT NULL,
  `to_name`    VARCHAR(120) NULL,
  `message`    VARCHAR(500) NOT NULL,
  `status`     ENUM('pending','sending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `error`      TEXT NULL,
  `sent_at`    DATETIME NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_smsq_club`   (`club_id`),
  KEY `idx_smsq_status` (`status`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kolejka wiadomości SMS';

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NULL COMMENT 'adresat (admin/trener) — NULL = dla całego klubu',
  `type`       VARCHAR(60) NOT NULL,
  `title`      VARCHAR(200) NOT NULL,
  `body`       TEXT NULL,
  `link`       VARCHAR(255) NULL,
  `read_at`    DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_notif_user` (`user_id`, `read_at`),
  KEY `idx_notif_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Powiadomienia in-app (dzwoneczek w navbarze)';

SET foreign_key_checks = 1;
-- Migration: 2FA TOTP (Phase 6.1)
SET foreign_key_checks = 0;

ALTER TABLE `users`
  ADD COLUMN `totp_secret`     VARCHAR(64) NULL AFTER `is_super_admin`,
  ADD COLUMN `totp_enabled`    TINYINT(1)  NOT NULL DEFAULT 0 AFTER `totp_secret`,
  ADD COLUMN `totp_confirmed_at` DATETIME NULL AFTER `totp_enabled`;

CREATE TABLE IF NOT EXISTS `totp_backup_codes` (
  `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`   INT UNSIGNED NOT NULL,
  `code_hash` VARCHAR(255) NOT NULL,
  `used_at`   DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tbc_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kody zapasowe 2FA (jednorazowe)';

SET foreign_key_checks = 1;
-- Football plugin migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `football_teams` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `name`            VARCHAR(120) NOT NULL,
  `age_category_id` INT UNSIGNED NULL,
  `league`          VARCHAR(100) NULL,
  `coach_id`        INT UNSIGNED NULL,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ft_club` (`club_id`),
  FOREIGN KEY (`club_id`)         REFERENCES `clubs`(`id`)          ON DELETE CASCADE,
  FOREIGN KEY (`age_category_id`) REFERENCES `age_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`coach_id`)        REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `football_matches` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `home_team_id`   INT UNSIGNED NOT NULL,
  `away_team`      VARCHAR(120) NOT NULL COMMENT 'nazwa druzyny przeciwnej (moze byc spoza systemu)',
  `away_team_id`   INT UNSIGNED NULL COMMENT 'jesli druzyna jest w systemie',
  `match_date`     DATETIME NOT NULL,
  `location`       VARCHAR(150) NULL,
  `home_score`     TINYINT UNSIGNED NULL,
  `away_score`     TINYINT UNSIGNED NULL,
  `referee`        VARCHAR(120) NULL,
  `league_round`   VARCHAR(40) NULL,
  `match_type`     ENUM('ligowy','pucharowy','towarzyski','turniejowy') NOT NULL DEFAULT 'ligowy',
  `status`         ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') NOT NULL DEFAULT 'zaplanowany',
  `notes`          TEXT NULL,
  `created_by`     INT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_fm_club` (`club_id`),
  KEY `idx_fm_date` (`match_date`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)          ON DELETE CASCADE,
  FOREIGN KEY (`home_team_id`)  REFERENCES `football_teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`away_team_id`)  REFERENCES `football_teams`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `football_match_events` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id`   INT UNSIGNED NOT NULL,
  `member_id`  INT UNSIGNED NOT NULL,
  `minute`     TINYINT UNSIGNED NULL,
  `type`       ENUM('gol','asysta','zolta_kartka','czerwona_kartka','zmiana_wejscie','zmiana_zejscie','kontuzja','karny_strzelony','karny_obroniony') NOT NULL,
  `notes`      VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_fme_match` (`match_id`),
  FOREIGN KEY (`match_id`)  REFERENCES `football_matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `football_lineups` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id`      INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `team_id`       INT UNSIGNED NOT NULL,
  `position`      ENUM('BR','OB','PM','NA','SR','LS','PS','LO','PO','SO','N') NULL COMMENT 'bramkarz/obrona/pomoc/atak',
  `is_starter`    TINYINT(1) NOT NULL DEFAULT 1,
  `jersey_number` TINYINT UNSIGNED NULL,
  UNIQUE KEY `uq_lineup` (`match_id`, `member_id`),
  FOREIGN KEY (`match_id`)  REFERENCES `football_matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)          ON DELETE CASCADE,
  FOREIGN KEY (`team_id`)   REFERENCES `football_teams`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `football_transfers` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`       INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `direction`     ENUM('przychodzacy','odchodzacy','wypozyczenie') NOT NULL,
  `from_club`     VARCHAR(150) NULL,
  `to_club`       VARCHAR(150) NULL,
  `transfer_date` DATE NOT NULL,
  `fee`           DECIMAL(12,2) NULL,
  `contract_until` DATE NULL,
  `notes`         TEXT NULL,
  `created_by`    INT UNSIGNED NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ftrans_club` (`club_id`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)  REFERENCES `members`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
-- Rollerskating plugin migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `rollerskating_equipment` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `member_id`   INT UNSIGNED NULL COMMENT 'NULL = sprzet klubowy',
  `type`        ENUM('wrotki','ochraniacze','kask','buty','kombinezon','inne') NOT NULL DEFAULT 'wrotki',
  `brand`       VARCHAR(80) NULL,
  `model`       VARCHAR(80) NULL,
  `size`        VARCHAR(20) NULL,
  `condition_state` ENUM('nowy','dobry','uzytkowy','do_serwisu','wycofany') NOT NULL DEFAULT 'dobry',
  `purchase_date` DATE NULL,
  `notes`       TEXT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_re_club` (`club_id`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rollerskating_times` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`       INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `discipline_id` INT UNSIGNED NULL,
  `event_id`      INT UNSIGNED NULL COMMENT 'powiazanie z events',
  `distance`      VARCHAR(30) NULL COMMENT 'np. 500m, 1000m, maraton',
  `time_ms`       INT UNSIGNED NOT NULL COMMENT 'wynik w milisekundach',
  `record_date`   DATE NOT NULL,
  `is_personal_best` TINYINT(1) NOT NULL DEFAULT 0,
  `notes`         TEXT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_rt_club` (`club_id`),
  KEY `idx_rt_member` (`member_id`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`event_id`)      REFERENCES `events`(`id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
-- Athletics plugin migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `athletics_records` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`          INT UNSIGNED NOT NULL,
  `member_id`        INT UNSIGNED NOT NULL,
  `discipline_id`    INT UNSIGNED NULL,
  `result_value`     DECIMAL(10,3) NOT NULL COMMENT 'wynik liczbowy',
  `result_unit`      ENUM('s','min','m','cm','kg') NOT NULL DEFAULT 's',
  `record_date`      DATE NOT NULL,
  `competition_name` VARCHAR(200) NULL,
  `location`         VARCHAR(150) NULL,
  `is_personal_best` TINYINT(1) NOT NULL DEFAULT 0,
  `is_club_record`   TINYINT(1) NOT NULL DEFAULT 0,
  `notes`            TEXT NULL,
  `created_by`       INT UNSIGNED NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ar_club`   (`club_id`),
  KEY `idx_ar_member` (`member_id`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)     REFERENCES `members`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`discipline_id`) REFERENCES `disciplines`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wyniki lekkoatletyczne (biegi, skoki, rzuty)';

SET foreign_key_checks = 1;
-- Volleyball plugin migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `volleyball_teams` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `name`            VARCHAR(120) NOT NULL,
  `league`          VARCHAR(100) NULL,
  `age_category_id` INT UNSIGNED NULL,
  `coach_id`        INT UNSIGNED NULL,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_vt_club` (`club_id`),
  FOREIGN KEY (`club_id`)         REFERENCES `clubs`(`id`)          ON DELETE CASCADE,
  FOREIGN KEY (`age_category_id`) REFERENCES `age_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`coach_id`)        REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `volleyball_matches` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `home_team_id`   INT UNSIGNED NOT NULL,
  `away_team`      VARCHAR(120) NOT NULL COMMENT 'nazwa druzyny przeciwnej (moze byc spoza systemu)',
  `away_team_id`   INT UNSIGNED NULL COMMENT 'jesli druzyna jest w systemie',
  `match_date`     DATETIME NOT NULL,
  `location`       VARCHAR(150) NULL,
  `set1_home`      TINYINT UNSIGNED NULL,
  `set1_away`      TINYINT UNSIGNED NULL,
  `set2_home`      TINYINT UNSIGNED NULL,
  `set2_away`      TINYINT UNSIGNED NULL,
  `set3_home`      TINYINT UNSIGNED NULL,
  `set3_away`      TINYINT UNSIGNED NULL,
  `set4_home`      TINYINT UNSIGNED NULL,
  `set4_away`      TINYINT UNSIGNED NULL,
  `set5_home`      TINYINT UNSIGNED NULL,
  `set5_away`      TINYINT UNSIGNED NULL,
  `home_sets`      TINYINT UNSIGNED NULL COMMENT 'total sets won by home',
  `away_sets`      TINYINT UNSIGNED NULL COMMENT 'total sets won by away',
  `home_score`     SMALLINT UNSIGNED NULL COMMENT 'total points scored by home',
  `away_score`     SMALLINT UNSIGNED NULL COMMENT 'total points scored by away',
  `referee`        VARCHAR(120) NULL,
  `match_type`     ENUM('ligowy','pucharowy','towarzyski','turniejowy') NOT NULL DEFAULT 'ligowy',
  `status`         ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') NOT NULL DEFAULT 'zaplanowany',
  `notes`          TEXT NULL,
  `created_by`     INT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_vm_club` (`club_id`),
  KEY `idx_vm_date` (`match_date`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)            ON DELETE CASCADE,
  FOREIGN KEY (`home_team_id`)  REFERENCES `volleyball_teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`away_team_id`)  REFERENCES `volleyball_teams`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `volleyball_player_stats` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id`    INT UNSIGNED NOT NULL,
  `member_id`   INT UNSIGNED NOT NULL,
  `attacks`     SMALLINT UNSIGNED NULL,
  `kills`       SMALLINT UNSIGNED NULL,
  `blocks`      SMALLINT UNSIGNED NULL,
  `serves`      SMALLINT UNSIGNED NULL,
  `aces`        SMALLINT UNSIGNED NULL,
  `digs`        SMALLINT UNSIGNED NULL,
  `errors`      SMALLINT UNSIGNED NULL,
  `sets_played` TINYINT UNSIGNED NULL,
  UNIQUE KEY `uq_vps_match_member` (`match_id`, `member_id`),
  FOREIGN KEY (`match_id`)  REFERENCES `volleyball_matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)            ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- Basketball plugin migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `basketball_teams` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`         INT UNSIGNED NOT NULL,
  `name`            VARCHAR(120) NOT NULL,
  `league`          VARCHAR(100) NULL,
  `age_category_id` INT UNSIGNED NULL,
  `coach_id`        INT UNSIGNED NULL,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_bt_club` (`club_id`),
  FOREIGN KEY (`club_id`)         REFERENCES `clubs`(`id`)          ON DELETE CASCADE,
  FOREIGN KEY (`age_category_id`) REFERENCES `age_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`coach_id`)        REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `basketball_matches` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `home_team_id`   INT UNSIGNED NOT NULL,
  `away_team`      VARCHAR(120) NOT NULL COMMENT 'nazwa druzyny przeciwnej (moze byc spoza systemu)',
  `away_team_id`   INT UNSIGNED NULL COMMENT 'jesli druzyna jest w systemie',
  `match_date`     DATETIME NOT NULL,
  `location`       VARCHAR(150) NULL,
  `q1_home`        TINYINT UNSIGNED NULL,
  `q1_away`        TINYINT UNSIGNED NULL,
  `q2_home`        TINYINT UNSIGNED NULL,
  `q2_away`        TINYINT UNSIGNED NULL,
  `q3_home`        TINYINT UNSIGNED NULL,
  `q3_away`        TINYINT UNSIGNED NULL,
  `q4_home`        TINYINT UNSIGNED NULL,
  `q4_away`        TINYINT UNSIGNED NULL,
  `overtime_home`  TINYINT UNSIGNED NULL,
  `overtime_away`  TINYINT UNSIGNED NULL,
  `home_score`     TINYINT UNSIGNED NULL,
  `away_score`     TINYINT UNSIGNED NULL,
  `referee`        VARCHAR(120) NULL,
  `match_type`     ENUM('ligowy','pucharowy','towarzyski','turniejowy') NOT NULL DEFAULT 'ligowy',
  `status`         ENUM('zaplanowany','w_trakcie','zakonczony','odwolany') NOT NULL DEFAULT 'zaplanowany',
  `notes`          TEXT NULL,
  `created_by`     INT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_bm_club` (`club_id`),
  KEY `idx_bm_date` (`match_date`),
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)            ON DELETE CASCADE,
  FOREIGN KEY (`home_team_id`)  REFERENCES `basketball_teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`away_team_id`)  REFERENCES `basketball_teams`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)    REFERENCES `users`(`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `basketball_player_stats` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `match_id`            INT UNSIGNED NOT NULL,
  `member_id`           INT UNSIGNED NOT NULL,
  `minutes`             SMALLINT UNSIGNED NULL,
  `points`              SMALLINT UNSIGNED NULL,
  `assists`             SMALLINT UNSIGNED NULL,
  `rebounds`            SMALLINT UNSIGNED NULL,
  `steals`              SMALLINT UNSIGNED NULL,
  `blocks`              SMALLINT UNSIGNED NULL,
  `turnovers`           SMALLINT UNSIGNED NULL,
  `fouls`               SMALLINT UNSIGNED NULL,
  `three_pointers`      SMALLINT UNSIGNED NULL,
  `free_throws_made`    SMALLINT UNSIGNED NULL,
  `free_throws_attempts` SMALLINT UNSIGNED NULL,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_bps_match_member` (`match_id`, `member_id`),
  KEY `idx_bps_match` (`match_id`),
  FOREIGN KEY (`match_id`)  REFERENCES `basketball_matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`)            ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
-- GDPR migration
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `member_consents` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`      INT UNSIGNED NOT NULL,
  `member_id`    INT UNSIGNED NOT NULL,
  `consent_type` ENUM('rodo','marketing','wizerunek','newsletter','profilowanie') NOT NULL,
  `granted`      TINYINT(1) NOT NULL DEFAULT 0,
  `granted_at`   DATETIME NULL,
  `revoked_at`   DATETIME NULL,
  `ip_address`   VARCHAR(45) NULL,
  `notes`        VARCHAR(255) NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_consent` (`club_id`, `member_id`, `consent_type`),
  KEY `idx_mc_member` (`member_id`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zgody RODO zawodnikow';

ALTER TABLE `members`
  ADD COLUMN `anonymized_at` DATETIME NULL AFTER `portal_last_login`;

-- ============================================================
-- WEBHOOKS
-- ============================================================

CREATE TABLE IF NOT EXISTS `webhook_endpoints` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NOT NULL,
  `url`        VARCHAR(500) NOT NULL,
  `secret`     VARCHAR(255) NOT NULL,
  `events`     JSON NOT NULL COMMENT '["member.created","payment.received"]',
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Webhook endpointy per klub';

CREATE TABLE IF NOT EXISTS `webhook_log` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `endpoint_id`   INT UNSIGNED NOT NULL,
  `event`         VARCHAR(80) NOT NULL,
  `payload`       JSON NULL,
  `response_code` SMALLINT NULL,
  `response_body` TEXT NULL,
  `sent_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`endpoint_id`) REFERENCES `webhook_endpoints`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log wyslanych webhookow';

-- ============================================================
-- DEMO TOKENS
-- ============================================================

CREATE TABLE IF NOT EXISTS `demo_tokens` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `token`      VARCHAR(64)  NOT NULL UNIQUE,
  `club_id`    INT UNSIGNED NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokeny demo do automatycznego logowania do klubu demo';

-- ============================================================
-- ADS / REKLAMY
-- ============================================================

CREATE TABLE IF NOT EXISTS `ads` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`       INT UNSIGNED NULL COMMENT 'NULL = globalny',
  `sport_id`      INT UNSIGNED NULL COMMENT 'audience=sport',
  `member_id`     INT UNSIGNED NULL COMMENT 'audience=member',
  `audience_type` ENUM('all','club','sport','member','plan') NOT NULL DEFAULT 'all',
  `title`         VARCHAR(150)  NOT NULL,
  `image_path`    VARCHAR(255)  NULL,
  `link_url`      VARCHAR(500)  NULL,
  `target`        ENUM('club_panel','member_portal','public') NOT NULL DEFAULT 'club_panel',
  `position`      ENUM('sidebar','top_banner','footer') NOT NULL DEFAULT 'top_banner',
  `plan_min`      VARCHAR(20)   NULL COMMENT 'minimalny plan subskrypcji',
  `start_date`    DATE          NULL,
  `end_date`      DATE          NULL,
  `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
  `impressions`   INT UNSIGNED  NOT NULL DEFAULT 0,
  `clicks`        INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ads_sport`    (`sport_id`),
  KEY `idx_ads_member`   (`member_id`),
  KEY `idx_ads_audience` (`audience_type`, `target`, `is_active`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`)  REFERENCES `sports`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Reklamy z targetowaniem per sport / per zawodnik / typ audiencji';

-- ============================================================
-- RATE LIMITING
-- ============================================================

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip`              VARCHAR(45)  NOT NULL,
  `action`          VARCHAR(60)  NOT NULL,
  `attempts`        INT UNSIGNED NOT NULL DEFAULT 0,
  `last_attempt_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `blocked_until`   DATETIME     NULL,
  UNIQUE KEY `uq_ip_action` (`ip`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rate limiting — ochrona przed brute-force';

SET foreign_key_checks = 1;
-- API keys for REST API v1
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `name`        VARCHAR(100) NOT NULL COMMENT 'opis klucza (np. "Strona WWW", "Mobilna apka")',
  `key_hash`    VARCHAR(255) NOT NULL COMMENT 'bcrypt hash klucza',
  `key_prefix`  VARCHAR(10)  NOT NULL COMMENT 'pierwsze 8 znaków (do identyfikacji)',
  `scopes`      JSON         NULL COMMENT '["members:read","events:read","payments:read"]',
  `rate_limit`  INT UNSIGNED NOT NULL DEFAULT 60 COMMENT 'max żądań/minutę',
  `last_used_at` DATETIME    NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ak_club` (`club_id`),
  UNIQUE KEY `uq_ak_prefix` (`key_prefix`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Klucze API per klub do REST API v1';

SET foreign_key_checks = 1;
-- Online payments for member portal
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `online_payments` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`       INT UNSIGNED NOT NULL,
  `member_id`     INT UNSIGNED NOT NULL,
  `fee_rate_id`   INT UNSIGNED NULL,
  `amount`        DECIMAL(10,2) NOT NULL,
  `currency`      CHAR(3) NOT NULL DEFAULT 'PLN',
  `description`   VARCHAR(200) NOT NULL,
  `period_year`   YEAR NULL,
  `period_month`  TINYINT UNSIGNED NULL,
  `provider`      ENUM('stripe','przelewy24','tpay','manual') NOT NULL DEFAULT 'stripe',
  `provider_id`   VARCHAR(255) NULL COMMENT 'ID transakcji u providera',
  `checkout_url`  VARCHAR(500) NULL,
  `status`        ENUM('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `paid_at`       DATETIME NULL,
  `ip_address`    VARCHAR(45) NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_op_club`   (`club_id`),
  KEY `idx_op_member` (`member_id`),
  KEY `idx_op_status` (`status`),
  FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`fee_rate_id`) REFERENCES `fee_rates`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Platnosci online z portalu zawodnika';

SET foreign_key_checks = 1;
-- Gallery: albums & photos
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `gallery_albums` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `sport_id`    INT UNSIGNED NULL,
  `event_id`    INT UNSIGNED NULL,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `cover_path`  VARCHAR(255) NULL,
  `is_public`   TINYINT(1) NOT NULL DEFAULT 0,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ga_club` (`club_id`),
  KEY `idx_ga_sport` (`sport_id`),
  KEY `idx_ga_event` (`event_id`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`sport_id`)   REFERENCES `sports`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`event_id`)   REFERENCES `events`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Albumy zdjec galerii klubowej';

CREATE TABLE IF NOT EXISTS `gallery_photos` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `album_id`       INT UNSIGNED NOT NULL,
  `file_path`      VARCHAR(255) NOT NULL,
  `thumbnail_path` VARCHAR(255) NULL,
  `caption`        VARCHAR(255) NULL,
  `uploaded_by`    INT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_gp_album` (`album_id`),
  FOREIGN KEY (`album_id`)    REFERENCES `gallery_albums`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zdjecia w albumach galerii';

SET foreign_key_checks = 1;
-- Internal messaging system
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `messages` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`        INT UNSIGNED NOT NULL,
  `sender_type`    ENUM('user','member') NOT NULL,
  `sender_id`      INT UNSIGNED NOT NULL,
  `recipient_type` ENUM('user','member','group') NOT NULL,
  `recipient_id`   INT UNSIGNED NULL,
  `group_scope`    ENUM('club','sport','team') NULL,
  `group_id`       INT UNSIGNED NULL,
  `subject`        VARCHAR(200) NOT NULL,
  `body`           TEXT NOT NULL,
  `parent_id`      INT UNSIGNED NULL COMMENT 'Thread parent message',
  `read_at`        DATETIME NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_msg_club`      (`club_id`),
  KEY `idx_msg_sender`    (`sender_type`, `sender_id`),
  KEY `idx_msg_recipient` (`recipient_type`, `recipient_id`),
  KEY `idx_msg_parent`    (`parent_id`),
  FOREIGN KEY (`club_id`)   REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `messages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wiadomosci wewnetrzne klubu';

CREATE TABLE IF NOT EXISTS `facilities` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NOT NULL,
  `name`        VARCHAR(150) NOT NULL,
  `type`        ENUM('boisko','sala','hala','tor','strzelnica','basen','kort','inne') NOT NULL DEFAULT 'inne',
  `capacity`    SMALLINT UNSIGNED NULL,
  `location`    VARCHAR(150) NULL,
  `description` TEXT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_fac_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Obiekty sportowe klubu';

CREATE TABLE IF NOT EXISTS `facility_bookings` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `facility_id`    INT UNSIGNED NOT NULL,
  `club_id`        INT UNSIGNED NOT NULL,
  `booked_by`      INT UNSIGNED NOT NULL,
  `booked_for_id`  INT UNSIGNED NULL COMMENT 'Opcjonalnie - rezerwacja dla zawodnika',
  `start_time`     DATETIME NOT NULL,
  `end_time`       DATETIME NOT NULL,
  `title`          VARCHAR(150) NOT NULL,
  `status`         ENUM('confirmed','pending','cancelled') NOT NULL DEFAULT 'confirmed',
  `notes`          TEXT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_fb_facility`  (`facility_id`),
  KEY `idx_fb_club`      (`club_id`),
  KEY `idx_fb_time`      (`start_time`, `end_time`),
  FOREIGN KEY (`facility_id`)   REFERENCES `facilities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`)       REFERENCES `clubs`(`id`)      ON DELETE CASCADE,
  FOREIGN KEY (`booked_by`)     REFERENCES `users`(`id`)      ON DELETE CASCADE,
  FOREIGN KEY (`booked_for_id`) REFERENCES `members`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rezerwacje obiektow sportowych';

-- ------------------------------------------------------------
-- dashboard_widgets (configurable per user)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dashboard_widgets` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `widget_key` VARCHAR(40)  NOT NULL,
  `position`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_visible` TINYINT(1)   NOT NULL DEFAULT 1,
  `config`     JSON         NULL,
  UNIQUE KEY `uq_user_widget` (`user_id`, `widget_key`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Konfiguracja widgetow dashboardu per uzytkownik';

SET foreign_key_checks = 1;
-- Migration: encrypted columns for sensitive data
-- pesel, email, phone → TEXT (ciphertext), _hash → VARCHAR(64) for lookups

ALTER TABLE `members`
  ADD COLUMN `pesel_hash` VARCHAR(64) NULL AFTER `pesel`,
  ADD COLUMN `email_hash` VARCHAR(64) NULL AFTER `email`,
  ADD COLUMN `phone_hash` VARCHAR(64) NULL AFTER `phone`;

ALTER TABLE `members` MODIFY `pesel` TEXT NULL;
ALTER TABLE `members` MODIFY `email` TEXT NULL;
ALTER TABLE `members` MODIFY `phone` TEXT NULL;

CREATE INDEX `idx_members_pesel_hash` ON `members` (`pesel_hash`);
CREATE INDEX `idx_members_email_hash` ON `members` (`email_hash`);
CREATE INDEX `idx_members_phone_hash` ON `members` (`phone_hash`);
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `livestreams` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`      INT UNSIGNED NOT NULL,
  `event_id`     INT UNSIGNED NULL,
  `title`        VARCHAR(200) NOT NULL,
  `platform`     ENUM('youtube','twitch','facebook','inne') NOT NULL DEFAULT 'youtube',
  `stream_url`   VARCHAR(500) NOT NULL,
  `embed_code`   TEXT NULL,
  `status`       ENUM('zaplanowana','na_zywo','zakonczona') NOT NULL DEFAULT 'zaplanowana',
  `scheduled_at` DATETIME NULL,
  `started_at`   DATETIME NULL,
  `ended_at`     DATETIME NULL,
  `viewers_peak` INT UNSIGNED NULL,
  `is_public`    TINYINT(1) NOT NULL DEFAULT 1,
  `created_by`   INT UNSIGNED NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_ls_club` (`club_id`),
  KEY `idx_ls_status` (`status`),
  FOREIGN KEY (`club_id`)    REFERENCES `clubs`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`event_id`)   REFERENCES `events`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- Migration 020: Admin overrides for club subscriptions
ALTER TABLE `club_subscriptions`
  ADD COLUMN `max_members_override` INT UNSIGNED NULL,
  ADD COLUMN `max_sports_override` TINYINT UNSIGNED NULL,
  ADD COLUMN `custom_features` JSON NULL,
  ADD COLUMN `admin_notes` TEXT NULL;

-- Migration 021: Unified member identities (cross-club identity)
CREATE TABLE IF NOT EXISTS `member_identities` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `identity_hash`     VARCHAR(64)  NOT NULL UNIQUE COMMENT 'SHA-256 of primary identifier',
  `portal_email`      VARCHAR(120) NOT NULL UNIQUE,
  `portal_password`   VARCHAR(255) NULL,
  `portal_last_login` DATETIME     NULL,
  `display_name`      VARCHAR(120) NOT NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `members` ADD COLUMN `identity_id` INT UNSIGNED NULL AFTER `club_id`;
ALTER TABLE `members` ADD CONSTRAINT `fk_member_identity` FOREIGN KEY (`identity_id`) REFERENCES `member_identities`(`id`) ON DELETE SET NULL;
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `device_tokens` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id`  INT UNSIGNED NOT NULL,
  `token`      VARCHAR(500) NOT NULL,
  `platform`   ENUM('android','ios','web') NOT NULL DEFAULT 'android',
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_token` (`token`(191)),
  KEY `idx_dt_member` (`member_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ============================================================
-- Migration 024: Shop (products, orders, order items)
-- ============================================================
SET foreign_key_checks = 0;
CREATE TABLE IF NOT EXISTS `shop_products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `category` ENUM('odzież','sprzęt','akcesoria','gadżety','inne') NOT NULL DEFAULT 'inne',
  `sizes` JSON NULL,
  `image_path` VARCHAR(255) NULL,
  `stock` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_sp_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shop_orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT UNSIGNED NOT NULL,
  `member_id` INT UNSIGNED NULL,
  `customer_name` VARCHAR(120) NOT NULL,
  `customer_email` VARCHAR(120) NULL,
  `customer_phone` VARCHAR(20) NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `status` ENUM('nowe','opłacone','w_realizacji','wysłane','odebrane','anulowane') NOT NULL DEFAULT 'nowe',
  `shipping_address` TEXT NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_so_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shop_order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `size` VARCHAR(20) NULL,
  FOREIGN KEY (`order_id`) REFERENCES `shop_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `shop_products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET foreign_key_checks = 1;

-- ============================================================
-- Migration 025: Result images (OCR manual upload)
-- ============================================================
CREATE TABLE IF NOT EXISTS `result_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT UNSIGNED NOT NULL,
  `event_id` INT UNSIGNED NULL,
  `member_id` INT UNSIGNED NULL,
  `sport_id` INT UNSIGNED NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `extracted_data` JSON NULL,
  `status` ENUM('uploaded','processed','verified') NOT NULL DEFAULT 'uploaded',
  `uploaded_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ri_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sport_id`) REFERENCES `sports`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Migration: password resets (027)
SET foreign_key_checks = 0;
CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(120) NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  type ENUM('user','member') NOT NULL DEFAULT 'user',
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pr_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET foreign_key_checks = 1;
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`    INT UNSIGNED NULL,
  `user_id`    INT UNSIGNED NULL,
  `subject`    VARCHAR(200) NOT NULL,
  `body`       TEXT NOT NULL,
  `priority`   ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status`     ENUM('open','in_progress','waiting','closed') NOT NULL DEFAULT 'open',
  `category`   ENUM('technical','billing','feature','bug','other') NOT NULL DEFAULT 'technical',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_st_club` (`club_id`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_replies` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_id`  INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED NULL,
  `body`       TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
