-- Migration 018: Dashboard widgets (configurable per user)
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
