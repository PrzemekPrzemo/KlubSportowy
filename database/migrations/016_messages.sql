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

SET foreign_key_checks = 1;
