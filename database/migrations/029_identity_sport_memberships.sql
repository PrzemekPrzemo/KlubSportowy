-- Migration 029: identity_sport_memberships
-- Pozwala jednemu zawodnikowi (member_identity) należeć do wielu sekcji
-- sportowych w wielu klubach jednocześnie. Każdy wpis = przynależność
-- (identity, klub, sport, member-rekord, rola).
--
-- Klucz dla cross-club logic: (identity_id, club_id, sport_key) UNIQUE
-- gwarantuje, że ten sam zawodnik nie ma dwóch wpisów w tej samej
-- sekcji tego samego klubu.

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `identity_sport_memberships` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `identity_id`  INT UNSIGNED NOT NULL,
  `club_id`      INT UNSIGNED NOT NULL,
  `sport_key`    VARCHAR(40)  NOT NULL,
  `member_id`    INT UNSIGNED NOT NULL COMMENT 'rekord members per (klub, sport)',
  `role`         ENUM('player','coach','staff','referee') NOT NULL DEFAULT 'player',
  `is_primary`   TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'sekcja domyślna w portalu zawodnika',
  `joined_at`    DATE NULL,
  `left_at`      DATE NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_ism` (`identity_id`, `club_id`, `sport_key`),
  KEY `idx_ism_identity` (`identity_id`),
  KEY `idx_ism_club_sport` (`club_id`, `sport_key`),
  KEY `idx_ism_member` (`member_id`),
  CONSTRAINT `fk_ism_identity` FOREIGN KEY (`identity_id`) REFERENCES `member_identities`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ism_club`     FOREIGN KEY (`club_id`)     REFERENCES `clubs`(`id`)             ON DELETE CASCADE,
  CONSTRAINT `fk_ism_member`   FOREIGN KEY (`member_id`)   REFERENCES `members`(`id`)           ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill z istniejących members: jeden wpis ISM per member
-- (identity_id, club_id, member_id) — sport_key z member_sports JOIN sports.key.
-- Jeśli member nie ma żadnego sportu — pomijamy (nie wiemy czego dotyczy).
INSERT IGNORE INTO `identity_sport_memberships`
  (`identity_id`, `club_id`, `sport_key`, `member_id`, `role`, `is_primary`, `joined_at`)
SELECT
  m.identity_id,
  m.club_id,
  s.`key`,
  m.id,
  'player',
  1,
  DATE(m.created_at)
FROM `members` m
JOIN `member_sports` ms ON ms.member_id = m.id
JOIN `club_sports`   cs ON cs.id        = ms.club_sport_id
JOIN `sports`        s  ON s.id         = cs.sport_id
WHERE m.identity_id IS NOT NULL;

SET foreign_key_checks = 1;
