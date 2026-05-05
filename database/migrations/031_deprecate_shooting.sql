-- Migration 031: deprecate Shooting sport (klucz 'shooting')
--
-- Strzelectwo zostalo wycofane z ClubDesk na rzecz zewnetrznego systemu
-- shootero.pl (zob. C1 / C2). Plugin app/Sports/Shooting/ zostal usuniety
-- (PR #8), wpis w katalogu sports usuniety z schema.sql, ale istniejace
-- instalacje produkcyjne moga miec rekordy w club_sports + tabelach
-- weapons/ammo/judge_licenses.
--
-- Ta migracja:
--   1. Deaktywuje wszystkie club_sports gdzie sport.key='shooting'
--      (is_active = 0). Dane sa zachowane — master admin moze recznie
--      podjac decyzje o usunieciu sekcji per klub.
--   2. Usuwa wpis z `sports` o key='shooting' jesli zadne club_sports
--      go juz nie referuje (rozluznia FK).
--
-- Jest idempotentna — bezpieczna do wielokrotnego uruchomienia.

SET foreign_key_checks = 0;

-- 1. Deaktywuj wszystkie aktywne sekcje strzeleckie w klubach.
UPDATE `club_sports` cs
JOIN `sports` s ON s.id = cs.sport_id
SET cs.is_active = 0
WHERE s.`key` = 'shooting'
  AND cs.is_active = 1;

-- 2. Usun wiersz w katalogu sports tylko jesli nic juz go nie referuje.
--    (Klubowe sekcje sa zachowane historycznie z is_active=0; jesli nie
--    ma zadnej, mozemy bezpiecznie usunac wpis. ON DELETE RESTRICT
--    nie pozwoli na usuniecie gdy istnieja referencje — wiec zostawia
--    dane producyjne nietkniete.)
DELETE FROM `sports`
WHERE `key` = 'shooting'
  AND id NOT IN (SELECT DISTINCT sport_id FROM `club_sports`);

SET foreign_key_checks = 1;
