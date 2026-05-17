-- ============================================================
-- 100_team_sports_stubs_full.sql
--
-- Doprowadza 6 sportow druzynowych do FULL functionality.
-- Ten plik trzyma tylko marker wersji — wszystkie tabele sport-specific
-- znajduja sie w app/Sports/<Sport>/migrations/ (uruchamiane PO root migrations).
--
-- Mapa tabel dodawanych przez ten release:
--   futsal:        sport_futsal_match_stats              (app/Sports/Futsal/migrations/002_*)
--   water_polo:    water_polo_teams/players/matches/events
--                  + sport_water_polo_match_stats       (app/Sports/WaterPolo/migrations/001_*)
--   curling:       curling_teams/players/matches
--                  + sport_curling_match_ends           (app/Sports/Curling/migrations/020_*)
--   rugby:         sport_rugby_match_scoring            (app/Sports/Rugby/migrations/002_*)
--   field_hockey:  sport_field_hockey_match_stats       (app/Sports/FieldHockey/migrations/002_*)
--   floorball:     sport_floorball_match_stats          (app/Sports/Floorball/migrations/002_*)
-- ============================================================

-- No-op marker migration. Tabele tworzone w sport-specific migracjach.
SELECT 1;
