-- ============================================================
-- 060_live_events.sql — live updates przez SSE
--
-- Tworzy:
--  * live_channels         — aktywne kanaly live (mecz/turniej/event)
--  * live_event_updates    — append-only log zdarzen do streamingu SSE
--
-- Architektura: publisher (POST /live/publish/:channel) INSERTuje
-- do live_event_updates; subscriber (GET /live/stream/:channel)
-- otwiera dlugotrwale polaczenie HTTP i SELECTuje co 2s nowe wiersze
-- po WHERE id > last_event_id, emitujac je jako Server-Sent Events.
-- ============================================================

CREATE TABLE IF NOT EXISTS `live_event_updates` (
    `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`      INT UNSIGNED NOT NULL,
    `channel`      VARCHAR(60) NOT NULL COMMENT 'np. tournament:42, match:88, event:103',
    `sequence_id`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'monotonic per channel',
    `event_type`   VARCHAR(40) NOT NULL COMMENT 'goal/point/quarter/timeout/foul/end/start/comment',
    `payload`      JSON NOT NULL,
    `created_at`   DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    KEY `idx_channel_id` (`channel`, `id`),
    KEY `idx_club_channel` (`club_id`, `channel`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `live_channels` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`         INT UNSIGNED NOT NULL,
    `channel`         VARCHAR(60) NOT NULL UNIQUE,
    `title`           VARCHAR(200) NOT NULL,
    `sport_key`       VARCHAR(40) NULL,
    `status`          ENUM('scheduled','live','finished') NOT NULL DEFAULT 'scheduled',
    `started_at`      DATETIME NULL,
    `ended_at`        DATETIME NULL,
    `is_public`       TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'widoczne na publicznej stronie klubu',
    `last_update_at`  DATETIME(3) NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_club_status` (`club_id`, `status`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
