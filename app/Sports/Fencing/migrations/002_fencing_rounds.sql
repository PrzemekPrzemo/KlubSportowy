ALTER TABLE fencing_results
    ADD COLUMN IF NOT EXISTS weapon          VARCHAR(10) DEFAULT NULL AFTER category,
    ADD COLUMN IF NOT EXISTS round_reached   VARCHAR(30) DEFAULT NULL AFTER weapon,
    ADD COLUMN IF NOT EXISTS ranking_points  INT DEFAULT NULL AFTER round_reached,
    ADD COLUMN IF NOT EXISTS team_event      TINYINT(1) NOT NULL DEFAULT 0 AFTER ranking_points;
