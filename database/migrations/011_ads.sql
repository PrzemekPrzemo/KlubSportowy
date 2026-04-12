-- Migration 011: Ads / reklamy
-- Allows super admin to manage advertisements displayed in club panels, portal, and public pages

CREATE TABLE IF NOT EXISTS `ads` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `club_id`     INT UNSIGNED NULL COMMENT 'NULL = globalny',
  `title`       VARCHAR(150)  NOT NULL,
  `image_path`  VARCHAR(255)  NULL,
  `link_url`    VARCHAR(500)  NULL,
  `target`      ENUM('club_panel','member_portal','public') NOT NULL DEFAULT 'club_panel',
  `position`    ENUM('sidebar','top_banner','footer') NOT NULL DEFAULT 'top_banner',
  `plan_min`    VARCHAR(20)   NULL COMMENT 'minimalny plan subskrypcji',
  `start_date`  DATE          NULL,
  `end_date`    DATE          NULL,
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `impressions` INT UNSIGNED  NOT NULL DEFAULT 0,
  `clicks`      INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Reklamy wyswietlane w panelu klubu, portalu zawodnika i stronach publicznych';
