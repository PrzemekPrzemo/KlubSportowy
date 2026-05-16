-- 087: Custom Report Builder — drag-drop kreator raportów dla użytkownika.
--
-- Dwie tabele:
--   * saved_reports — zapisane definicje raportów (per klub, per użytkownik).
--                     config_json zawiera pełną specyfikację: data_source, columns,
--                     filters, group_by, aggregations, order_by, limit, chart.
--                     ReportBuilder wykonuje raporty WYŁĄCZNIE z whitelistowanego
--                     DataSourceRegistry (security: zero raw SQL z user input).
--   * report_runs   — audyt wykonań (kto, kiedy, ile wierszy, jak długo trwało).
--                     Pozwala na statystyki wydajności i wykrywanie nadużyć.
--
-- Szablony globalne (club_id = NULL) — predefiniowane raporty kopiowane do nowych klubów.

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `saved_reports` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`            INT UNSIGNED NULL COMMENT 'NULL = globalny szablon',
    `created_by_user_id` INT UNSIGNED NULL COMMENT 'NULL dla globalnych szablonów',
    `name`               VARCHAR(200) NOT NULL,
    `description`        TEXT NULL,
    `data_source`        ENUM('members','trainings','payments','tournaments','attendance','sponsors') NOT NULL,
    `config_json`        LONGTEXT NOT NULL COMMENT 'JSON: columns, filters, group_by, aggregations, order_by, limit, chart',
    `is_shared`          TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=widoczny dla innych userów w klubie',
    `is_template`        TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=globalny szablon (club_id=NULL)',
    `last_run_at`        DATETIME NULL,
    `run_count`          INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_sr_club_user` (`club_id`, `created_by_user_id`),
    KEY `idx_sr_data_source` (`data_source`),
    KEY `idx_sr_template` (`is_template`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Definicje zapisanych raportów custom builder (config_json = whitelistowany)';

CREATE TABLE IF NOT EXISTS `report_runs` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id`     INT UNSIGNED NOT NULL,
    `user_id`       INT UNSIGNED NULL,
    `rows_returned` INT UNSIGNED NULL,
    `duration_ms`   INT UNSIGNED NULL,
    `run_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_rr_report_date` (`report_id`, `run_at`),
    FOREIGN KEY (`report_id`) REFERENCES `saved_reports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audyt wykonań raportów custom builder';

SET foreign_key_checks = 1;

-- ─────────────────────────────────────────────────────────────
-- Predefiniowane globalne szablony (club_id = NULL).
-- Kopiowane do każdego nowego klubu przez OnboardingWizardController.
-- Wszystkie pola w config_json MUSZĄ być whitelistowane w DataSourceRegistry.
-- ─────────────────────────────────────────────────────────────

INSERT INTO `saved_reports` (`club_id`, `created_by_user_id`, `name`, `description`, `data_source`, `config_json`, `is_template`)
VALUES
(NULL, NULL, 'Aktywni członkowie według miasta', 'Liczba aktywnych zawodników z podziałem na miasta — przydatne do planowania logistyki.', 'members',
 '{"columns":["address_city"],"filters":[{"field":"status","op":"=","value":"aktywny"}],"group_by":["address_city"],"aggregations":[{"field":"id","fn":"count","alias":"liczba_zawodnikow"}],"order_by":[{"field":"liczba_zawodnikow","dir":"desc"}],"limit":100,"chart":{"type":"bar","x":"address_city","y":"liczba_zawodnikow"}}',
 1),
(NULL, NULL, 'Najwięcej obecności w tym miesiącu', 'Top 20 zawodników z największą liczbą obecności na treningach (status=obecny).', 'attendance',
 '{"columns":["member_full_name"],"filters":[{"field":"status","op":"=","value":"obecny"}],"group_by":["member_full_name"],"aggregations":[{"field":"id","fn":"count","alias":"liczba_obecnosci"}],"order_by":[{"field":"liczba_obecnosci","dir":"desc"}],"limit":20,"chart":{"type":"bar","x":"member_full_name","y":"liczba_obecnosci"}}',
 1),
(NULL, NULL, 'Płatności — sumy roczne', 'Suma wpłaconych kwot z podziałem na rok rozliczeniowy.', 'payments',
 '{"columns":["period_year"],"filters":[],"group_by":["period_year"],"aggregations":[{"field":"amount","fn":"sum","alias":"suma_wplat"}],"order_by":[{"field":"period_year","dir":"desc"}],"limit":20,"chart":{"type":"bar","x":"period_year","y":"suma_wplat"}}',
 1),
(NULL, NULL, 'Wyniki turniejowe — top 10 zawodników', 'Lista 10 turniejów z największą liczbą uczestników.', 'tournaments',
 '{"columns":["name","date_start","status"],"filters":[],"group_by":[],"aggregations":[],"order_by":[{"field":"date_start","dir":"desc"}],"limit":10,"chart":{"type":"none"}}',
 1),
(NULL, NULL, 'Frekwencja na treningach (ostatnie wpisy)', 'Statusy obecności na ostatnich treningach z grupowaniem po statusie.', 'attendance',
 '{"columns":["status"],"filters":[],"group_by":["status"],"aggregations":[{"field":"id","fn":"count","alias":"liczba"}],"order_by":[{"field":"liczba","dir":"desc"}],"limit":100,"chart":{"type":"pie","x":"status","y":"liczba"}}',
 1);
