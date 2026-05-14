-- 057_sport_ranking_strategy.sql
-- Auto-rankings engine: kolumna `sports.ranking_strategy` + `sport_rankings.last_calculated_at`.

ALTER TABLE `sports`
  ADD COLUMN `ranking_strategy` VARCHAR(40) NOT NULL DEFAULT 'league_points'
  COMMENT 'elo | league_points | best_time — wybór strategii dla auto-recalc rankingu'
  AFTER `team_sport`;

-- Sensowne domyślne mapowania na podstawie typu sportu.
UPDATE `sports` SET `ranking_strategy` = 'elo'
  WHERE `key` IN ('tennis','squash','padel','chess','table_tennis','badminton');

UPDATE `sports` SET `ranking_strategy` = 'best_time'
  WHERE `key` IN ('swimming','athletics','rollerskating','shooting','cycling','rowing');

UPDATE `sports` SET `ranking_strategy` = 'league_points'
  WHERE `key` IN ('football','basketball','volleyball','handball');

-- Cache busting / observability dla auto-recalc.
ALTER TABLE `sport_rankings`
  ADD COLUMN `last_calculated_at` DATETIME NULL
  COMMENT 'Ostatnie auto-przeliczenie rankingu (RankingEngine)'
  AFTER `updated_at`;
