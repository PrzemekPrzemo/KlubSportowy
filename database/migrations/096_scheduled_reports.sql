-- Migration 096: Scheduled PDF dashboards do email
--
-- Zarzad klubu konfiguruje email z PDF KPI co tydzien / miesiac / kwartal.
-- Cron `cli/run_scheduled_reports.php` co godzine przeglada `next_send_at`.
-- Po wygenerowaniu PDF rekordy ladowane sa do `scheduled_report_runs`
-- (audyt + ponowne pobranie PDF z UI).
--
-- Multi-tenant: kazda zaplanowana definicja oraz jej run sa zwiazane z club_id
-- (run_id -> report_id -> club_id). Kontroler musi sprawdzac club_id w WHERE.

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `scheduled_reports` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`          INT UNSIGNED NOT NULL,
    `name`             VARCHAR(200)  NOT NULL,
    `recipient_emails` TEXT          NOT NULL COMMENT 'JSON array of emails',
    `cron_schedule`    ENUM('weekly_mon','weekly_fri','monthly_1st','quarterly') NOT NULL,
    `template`         ENUM('club_summary','financial','attendance','full_dashboard') NOT NULL DEFAULT 'full_dashboard',
    `config_json`      JSON          NULL COMMENT 'opt-in sekcje',
    `active`           TINYINT(1)    NOT NULL DEFAULT 1,
    `last_sent_at`     DATETIME      NULL,
    `next_send_at`     DATETIME      NULL,
    `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sr_next_send` (`active`, `next_send_at`),
    KEY `idx_sr_club`      (`club_id`),
    CONSTRAINT `fk_scheduled_reports_club`
        FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Definicje cyklicznych PDF dashboardow wysylanych do zarzadu';

CREATE TABLE IF NOT EXISTS `scheduled_report_runs` (
    `id`               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id`        INT UNSIGNED   NOT NULL,
    `pdf_path`         VARCHAR(500)   NULL,
    `pdf_size_bytes`   INT            NULL,
    `recipients_count` INT            NULL,
    `status`           ENUM('generated','sent','failed') NOT NULL,
    `error_message`    TEXT           NULL,
    `generated_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `sent_at`          DATETIME       NULL,
    KEY `idx_srr_report` (`report_id`, `generated_at`),
    CONSTRAINT `fk_scheduled_report_runs_report`
        FOREIGN KEY (`report_id`) REFERENCES `scheduled_reports`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historia uruchomien zaplanowanych raportow PDF';

-- Email event w katalogu (uzywany przez UI / EmailService)
INSERT IGNORE INTO `email_event_catalog`
    (`code`, `name`, `description`, `category`, `default_subject`, `default_body`, `available_variables`, `sort_order`)
VALUES (
    'scheduled_report_ready',
    'Raport zaplanowany',
    'Cykliczny dashboard PDF z KPI klubu (members/finanse/frekwencja/eventy).',
    'reports',
    '[{{club_name}}] Raport tygodniowy {{date_range}}',
    'Czesc,\n\nW zalaczniku Twoj raport tygodniowy klubu {{club_name}} za okres {{date_range}}.\n\nKluczowe wskazniki:\n- Aktywni czlonkowie: {{kpi_members}}\n- Srednia frekwencja: {{kpi_attendance}}%\n- Wplywy: {{kpi_revenue}} PLN\n\nPelne dane w zalaczonym PDF.\n\nPozdrawiamy,\nClubDesk',
    '["club_name","date_range","kpi_members","kpi_attendance","kpi_revenue","kpi_overdue"]',
    200
);

SET foreign_key_checks = 1;
