-- Migration 093: Auto-publikowane protokoly turniejowe (PDF + public share link)
--
-- Po zakonczeniu turnieju (tournaments.status = 'finished') system automatycznie
-- generuje PDF protokol przez TournamentProtocolPdf (PR #151) i zapisuje meta
-- w `tournament_protocols`. Opcjonalnie wystawia publiczny share link (slug)
-- analogiczny do live scoring (PR #181) — bez logowania, opt-in admina.
--
-- Wersjonowanie:
--   (tournament_id, version) UNIQUE — kazdy republish bumpuje version + 1.
--   public_share_slug stay-the-same — link nigdy nie wygasa po regeneracji PDF.
--
-- Storage:
--   pdf_path = storage/tournament_protocols/{club_id}/{tournament_id}_v{version}.pdf
--   POZA /public/ — wymusza pobieranie przez kontroler (rate-limit + flag check).

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `tournament_protocols` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tournament_id`        INT UNSIGNED NOT NULL,
    `club_id`              INT UNSIGNED NOT NULL,
    `version`              INT          NOT NULL DEFAULT 1,
    `pdf_path`             VARCHAR(500) NOT NULL
        COMMENT 'Sciezka relatywna do ROOT_PATH (poza /public/!)',
    `pdf_size_bytes`       INT          NULL,
    `pdf_hash`             CHAR(64)     NULL
        COMMENT 'SHA-256 zawartosci PDF (cache-busting + integrity check)',
    `public_share_slug`    VARCHAR(80)  NULL UNIQUE
        COMMENT 'Globalnie unikalny slug dla /protocols/{slug} (jak live scoring)',
    `public_share_enabled` TINYINT(1)   NOT NULL DEFAULT 0
        COMMENT 'Czy publiczny share link jest wlaczony (opt-in admin)',
    `generated_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `auto_generated`       TINYINT(1)   NOT NULL DEFAULT 1
        COMMENT '1 = wygenerowany automatycznie po status=finished, 0 = manualny refresh',
    UNIQUE KEY `uniq_tournament_version` (`tournament_id`, `version`),
    KEY `idx_club` (`club_id`),
    KEY `idx_generated` (`generated_at`),
    CONSTRAINT `fk_tp_tournament`
        FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tp_club`
        FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wersjonowane PDF-y protokolow turniejowych (+ public share link)';

-- Email event catalog: tournament_finished_protocol
-- (Tabela email_event_catalog jest tworzona w migracji 069).
INSERT IGNORE INTO `email_event_catalog`
    (`code`, `name`, `description`, `category`, `default_subject`, `default_body`, `available_variables`, `sort_order`)
VALUES
('tournament_finished_protocol', 'Protokol turnieju (po zakonczeniu)',
 'Wysylany do uczestnikow po opublikowaniu publicznego protokolu PDF',
 'events',
 'Protokol turnieju: {{tournament.name}}',
 'Czesc {{member.first_name}},\n\nTurniej {{tournament.name}} ({{tournament.date}}) zostal zakonczony.\nPelny protokol (PDF) dostepny jest pod adresem:\n\n{{share_url}}\n\nPozdrawiamy,\n{{club.name}}',
 '["member.first_name","tournament.name","tournament.date","share_url","club.name"]',
 65);

SET foreign_key_checks = 1;
