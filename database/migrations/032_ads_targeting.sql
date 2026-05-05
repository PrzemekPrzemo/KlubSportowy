-- Migration 032: Ads — targeting per sport / per member / audience type
--
-- Rozszerza tabele `ads` o mozliwosc targetowania reklam:
--   - sport_id  → ograniczenie do konkretnego sportu
--   - member_id → reklama widoczna tylko dla wskazanego zawodnika
--   - audience_type → semantyczny filtr (kogo dotyczy reklama):
--       'all'    — domyslnie, reklama uniwersalna
--       'club'   — tylko klub wskazany w ads.club_id
--       'sport'  — tylko zawodnicy/admini z sekcja sport_id
--       'member' — tylko zawodnik member_id
--       'plan'   — wszyscy na planie ads.plan_min lub wyzszym
--
-- Logika filtrowania jest w AdModel::activeForTarget (D3).
-- Migracja jest additive — istniejace reklamy domyslnie maja audience_type='all'.

SET foreign_key_checks = 0;

ALTER TABLE `ads`
  ADD COLUMN `sport_id`      INT UNSIGNED NULL  AFTER `club_id`,
  ADD COLUMN `member_id`     INT UNSIGNED NULL  AFTER `sport_id`,
  ADD COLUMN `audience_type` ENUM('all','club','sport','member','plan')
                             NOT NULL DEFAULT 'all' AFTER `member_id`;

ALTER TABLE `ads`
  ADD KEY `idx_ads_sport`    (`sport_id`),
  ADD KEY `idx_ads_member`   (`member_id`),
  ADD KEY `idx_ads_audience` (`audience_type`, `target`, `is_active`);

ALTER TABLE `ads`
  ADD CONSTRAINT `fk_ads_sport`
    FOREIGN KEY (`sport_id`)  REFERENCES `sports`(`id`)  ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ads_member`
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE;

SET foreign_key_checks = 1;
