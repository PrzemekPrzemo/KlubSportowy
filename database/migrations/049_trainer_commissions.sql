-- ============================================================
-- Migracja 049_trainer_commissions.sql
--
-- Faza U.0 — system prowizji dla trenerów.
--
-- Trener (user z rolą 'trener') ma stawkę prowizji od składek
-- swoich zawodników:
--   - percent (np. 30% od każdej składki)
--   - fixed_amount (np. 50zł stała kwota od każdej wpłaconej składki)
--   - sport-specific lub klubowa (NULL = wszystkie sporty)
--
-- Przy zaksięgowaniu wpłaty (payments.insert) auto-naliczamy
-- commissions log entry per trener który prowadzi tego zawodnika
-- (relacja member_sports.coach_id lub trainings.coach_id w którym
-- zawodnik uczestniczy).
-- ============================================================

SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- trainer_commission_rates — stawki prowizji
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trainer_commission_rates` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`          INT UNSIGNED NOT NULL,
    `trainer_user_id`  INT UNSIGNED NOT NULL COMMENT 'users.id z user_clubs.role=trener',
    `sport_id`         INT UNSIGNED NULL COMMENT 'NULL = wszystkie sporty (klubowa)',
    `commission_type`  ENUM('percent','fixed_amount') NOT NULL DEFAULT 'percent',
    `value`            DECIMAL(10,2) NOT NULL COMMENT 'percent: 0-100, fixed_amount: PLN',
    `applies_to`       ENUM('skladka','wpisowe','licencja','all') NOT NULL DEFAULT 'skladka'
                       COMMENT 'którego fee_type dotyczy prowizja',
    `valid_from`       DATE NOT NULL DEFAULT (CURRENT_DATE),
    `valid_to`         DATE NULL,
    `is_active`        TINYINT(1) NOT NULL DEFAULT 1,
    `notes`            TEXT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_trainer_sport` (`club_id`, `trainer_user_id`, `sport_id`, `applies_to`),
    KEY `idx_tcr_active` (`is_active`, `valid_from`, `valid_to`),
    FOREIGN KEY (`club_id`)         REFERENCES `clubs`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`trainer_user_id`) REFERENCES `users`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`sport_id`)        REFERENCES `sports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stawki prowizji trenerów — % lub kwota stała per sport';

-- ------------------------------------------------------------
-- trainer_commissions_log — naliczone prowizje (per wpłata)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trainer_commissions_log` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NOT NULL,
    `trainer_user_id` INT UNSIGNED NOT NULL,
    `payment_id`      INT UNSIGNED NOT NULL COMMENT 'payments.id (wpłata zawodnika)',
    `member_id`       INT UNSIGNED NOT NULL,
    `rate_id`         INT UNSIGNED NULL COMMENT 'trainer_commission_rates.id',
    `commission_type` ENUM('percent','fixed_amount') NOT NULL,
    `rate_value`      DECIMAL(10,2) NOT NULL COMMENT 'snapshot wartości stawki',
    `payment_amount`  DECIMAL(10,2) NOT NULL COMMENT 'kwota wpłaty od której naliczono',
    `commission_amount` DECIMAL(10,2) NOT NULL COMMENT 'wyliczona prowizja',
    `period_year`     YEAR NOT NULL,
    `period_month`    TINYINT UNSIGNED NULL,
    `status`          ENUM('accrued','paid_out','cancelled') NOT NULL DEFAULT 'accrued'
                      COMMENT 'accrued=naliczona | paid_out=wypłacona trenerowi | cancelled',
    `paid_out_at`     DATETIME NULL,
    `notes`           TEXT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_payment_trainer` (`payment_id`, `trainer_user_id`),
    KEY `idx_tcl_trainer_period` (`trainer_user_id`, `period_year`, `period_month`),
    KEY `idx_tcl_club_status`    (`club_id`, `status`),
    FOREIGN KEY (`club_id`)         REFERENCES `clubs`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`trainer_user_id`) REFERENCES `users`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`payment_id`)      REFERENCES `payments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_id`)       REFERENCES `members`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`rate_id`)         REFERENCES `trainer_commission_rates`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit log naliczonych prowizji trenerów per wpłata zawodnika';

SET foreign_key_checks = 1;
