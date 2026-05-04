ALTER TABLE badminton_results
    ADD COLUMN IF NOT EXISTS sets_won               TINYINT UNSIGNED DEFAULT NULL AFTER category,
    ADD COLUMN IF NOT EXISTS sets_lost              TINYINT UNSIGNED DEFAULT NULL AFTER sets_won,
    ADD COLUMN IF NOT EXISTS ranking_points_before  INT DEFAULT NULL AFTER sets_lost,
    ADD COLUMN IF NOT EXISTS ranking_points_after   INT DEFAULT NULL AFTER ranking_points_before;
