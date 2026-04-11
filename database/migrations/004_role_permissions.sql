-- Migration: role permissions matrix (Phase 2.3b)
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `club_id`   INT UNSIGNED NULL COMMENT 'NULL = globalny domyślny',
  `role`      VARCHAR(40) NOT NULL,
  `module`    VARCHAR(40) NOT NULL,
  `can_view`  TINYINT(1)  NOT NULL DEFAULT 0,
  `can_edit`  TINYINT(1)  NOT NULL DEFAULT 0,
  `updated_at` DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`club_id`, `role`, `module`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Macierz uprawnień rola x modul (NULL club_id = domyślne)';

-- Seed domyślnych ról (globalnych — club_id NULL)
-- zarzad ma pełen dostęp
INSERT IGNORE INTO role_permissions (club_id, role, module, can_view, can_edit) VALUES
  (NULL, 'zarzad', 'members',       1, 1),
  (NULL, 'zarzad', 'sports',        1, 1),
  (NULL, 'zarzad', 'fees',          1, 1),
  (NULL, 'zarzad', 'events',        1, 1),
  (NULL, 'zarzad', 'trainings',     1, 1),
  (NULL, 'zarzad', 'calendar',      1, 1),
  (NULL, 'zarzad', 'medical',       1, 1),
  (NULL, 'zarzad', 'announcements', 1, 1),
  (NULL, 'zarzad', 'club',          1, 1),

  (NULL, 'trener', 'members',       1, 1),
  (NULL, 'trener', 'events',        1, 1),
  (NULL, 'trener', 'trainings',     1, 1),
  (NULL, 'trener', 'calendar',      1, 1),
  (NULL, 'trener', 'medical',       1, 0),
  (NULL, 'trener', 'announcements', 1, 1),

  (NULL, 'instruktor', 'members',   1, 0),
  (NULL, 'instruktor', 'events',    1, 0),
  (NULL, 'instruktor', 'trainings', 1, 1),
  (NULL, 'instruktor', 'calendar',  1, 0),
  (NULL, 'instruktor', 'announcements', 1, 0),

  (NULL, 'sedzia', 'events',        1, 1),
  (NULL, 'sedzia', 'calendar',      1, 0),

  (NULL, 'lekarz', 'members',       1, 0),
  (NULL, 'lekarz', 'medical',       1, 1),

  (NULL, 'ksiegowy', 'members',     1, 0),
  (NULL, 'ksiegowy', 'fees',        1, 1);

SET foreign_key_checks = 1;
