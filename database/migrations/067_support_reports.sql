-- 067: support_reports — system zglaszania bledow i propozycji od uzytkownikow
-- klubowych oraz portal-memberow, z synchronizacja do Todoist.
-- Tabela jest niezalezna od istniejacej `support_tickets` (klub -> platforma).

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `support_reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id` INT UNSIGNED NULL COMMENT 'NULL gdy zglaszal portal-member z innej drogi',
    `user_id` INT UNSIGNED NULL COMMENT 'klub admin/zarzad/trener etc.',
    `member_id` INT UNSIGNED NULL COMMENT 'jesli zglaszal portal-member',
    `submitter_name` VARCHAR(120) NULL COMMENT 'fallback display name',
    `submitter_email` VARCHAR(120) NULL,
    `type` ENUM('bug','feature','question','other') NOT NULL DEFAULT 'bug',
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `screenshot_path` VARCHAR(500) NULL,
    `url_context` VARCHAR(500) NULL COMMENT 'URL strony skad zglosil',
    `user_agent` VARCHAR(500) NULL,
    `status` ENUM('new','in_progress','resolved','wont_fix','duplicate') NOT NULL DEFAULT 'new',
    `todoist_task_id` VARCHAR(60) NULL COMMENT 'ID zadania w Todoist po synchronizacji',
    `todoist_synced_at` DATETIME NULL,
    `todoist_sync_error` TEXT NULL,
    `resolved_at` DATETIME NULL,
    `resolved_by` INT UNSIGNED NULL,
    `resolution_notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_sr_club_status` (`club_id`, `status`),
    KEY `idx_sr_status_created` (`status`, `created_at`),
    KEY `idx_sr_todoist` (`todoist_task_id`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
