-- 085: Messenger — real-time chat klubowy
--
-- Trzy tabele wspierajace czat 1-1, grupowy oraz per sport_section:
--   * message_threads               — watki rozmow (typ + opcjonalne FK na section/event)
--   * message_thread_participants   — czlonkowie watkow + last_read_message_id (unread tracking)
--   * chat_messages                 — wiadomosci (BIGINT id, soft delete via deleted_at)
--
-- Realtime: SSE (PortalMessengerController::stream) + long-poll fallback.
-- Multi-tenant: club_id ON DELETE CASCADE na wszystkich glownych tabelach.

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `message_threads` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    thread_type ENUM('direct','group','section','event') NOT NULL DEFAULT 'direct',
    title VARCHAR(200) NULL,
    section_id INT UNSIGNED NULL,
    event_id INT UNSIGNED NULL,
    created_by_member_id INT UNSIGNED NOT NULL,
    last_message_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_club_lastmsg (club_id, last_message_at),
    KEY idx_thread_type (thread_type),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Watki rozmow w komunikatorze klubowym';

CREATE TABLE IF NOT EXISTS `message_thread_participants` (
    thread_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_read_message_id BIGINT UNSIGNED NULL,
    notifications_muted TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (thread_id, member_id),
    KEY idx_member (member_id),
    FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Czlonkowie watku + unread tracking';

CREATE TABLE IF NOT EXISTS `chat_messages` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id INT UNSIGNED NOT NULL,
    sender_member_id INT UNSIGNED NOT NULL,
    club_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    attachment_path VARCHAR(500) NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_thread_created (thread_id, created_at),
    KEY idx_thread_id (thread_id, id),
    KEY idx_club (club_id),
    FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wiadomosci w czacie (BIGINT id pod szybki rozrost)';

SET foreign_key_checks = 1;
