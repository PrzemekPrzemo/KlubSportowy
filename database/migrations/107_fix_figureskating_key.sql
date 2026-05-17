-- ============================================================
-- Migracja 107: konsolidacja klucza figure_skating → figureskating
--
-- Problem:
--   - migracja 002_niche_sports.sql seedowała key='figure_skating'
--   - migracja 064_sports_seed_expansion.sql seedowała key='figureskating'
--   - manifest plugina (app/Sports/FigureSkating/manifest.php) używa 'figureskating'
--   - powstały duplikat w bazie + disciplines zostały podpięte do starego klucza
--
-- Fix (idempotentny, bezpieczny dla istniejących baz):
--   1. Jeśli istnieje 'figureskating' — przepisz disciplines/member_classes z 'figure_skating'
--   2. Jeśli istnieje TYLKO 'figure_skating' — przemianuj na 'figureskating'
--   3. Usuń osierocony 'figure_skating' (po przeniesieniu zależności)
-- ============================================================

SET @figureskating_id   = (SELECT id FROM `sports` WHERE `key` = 'figureskating'  LIMIT 1);
SET @figure_skating_id  = (SELECT id FROM `sports` WHERE `key` = 'figure_skating' LIMIT 1);

-- Step 1: jeśli mamy oba — przerzuć dyscypliny i klasy do nowego klucza
UPDATE `disciplines`
   SET `sport_id` = @figureskating_id
 WHERE @figureskating_id IS NOT NULL
   AND @figure_skating_id IS NOT NULL
   AND @figureskating_id <> @figure_skating_id
   AND `sport_id` = @figure_skating_id;

UPDATE `member_classes`
   SET `sport_id` = @figureskating_id
 WHERE @figureskating_id IS NOT NULL
   AND @figure_skating_id IS NOT NULL
   AND @figureskating_id <> @figure_skating_id
   AND `sport_id` = @figure_skating_id;

-- Step 2: jeśli mamy tylko stary klucz — przemianuj go (rename in place)
UPDATE `sports`
   SET `key` = 'figureskating'
 WHERE `key` = 'figure_skating'
   AND NOT EXISTS (SELECT 1 FROM (SELECT id FROM `sports` WHERE `key` = 'figureskating' LIMIT 1) AS t);

-- Step 3: jeśli oba istnieją równocześnie i osierocony nie ma już zależności — usuń
DELETE FROM `sports`
 WHERE `key` = 'figure_skating'
   AND EXISTS (SELECT 1 FROM (SELECT id FROM `sports` WHERE `key` = 'figureskating' LIMIT 1) AS t)
   AND NOT EXISTS (SELECT 1 FROM `disciplines`     WHERE `sport_id` = (SELECT id FROM (SELECT id FROM `sports` WHERE `key` = 'figure_skating' LIMIT 1) AS x))
   AND NOT EXISTS (SELECT 1 FROM `member_classes`  WHERE `sport_id` = (SELECT id FROM (SELECT id FROM `sports` WHERE `key` = 'figure_skating' LIMIT 1) AS x))
   AND NOT EXISTS (SELECT 1 FROM `club_sports`     WHERE `sport_id` = (SELECT id FROM (SELECT id FROM `sports` WHERE `key` = 'figure_skating' LIMIT 1) AS x));
