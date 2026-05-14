-- Fencing precision-sport base migration
-- Walki (bouts) z bronia: floret/szpada/szabla.
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `fencing_bouts` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    tournament_id INT UNSIGNED NULL,
    fencer_a_id INT UNSIGNED NOT NULL,
    fencer_b_id INT UNSIGNED NOT NULL,
    weapon ENUM('foil','epee','sabre') NOT NULL,
    score_a TINYINT UNSIGNED NULL,
    score_b TINYINT UNSIGNED NULL,
    winner_id INT UNSIGNED NULL,
    duration_seconds INT UNSIGNED NULL,
    bout_format VARCHAR(20) NULL COMMENT '15-touch/5-touch/...',
    played_at DATETIME NULL,
    KEY idx_club_date (club_id, played_at),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (fencer_a_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (fencer_b_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
