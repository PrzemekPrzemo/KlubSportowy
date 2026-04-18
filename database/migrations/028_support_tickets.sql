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
