-- Boxing: rozszerzenie o dane walki + tabela badań lekarskich
SET foreign_key_checks = 0;

ALTER TABLE boxing_results
    ADD COLUMN IF NOT EXISTS opponent_name  VARCHAR(150) DEFAULT NULL AFTER weight_class,
    ADD COLUMN IF NOT EXISTS result         ENUM('win','loss','draw','nc','dq') DEFAULT NULL AFTER opponent_name,
    ADD COLUMN IF NOT EXISTS method         ENUM('ko','tko','points','decision','disq','nc') DEFAULT NULL AFTER result,
    ADD COLUMN IF NOT EXISTS rounds_total   TINYINT UNSIGNED DEFAULT NULL AFTER method,
    ADD COLUMN IF NOT EXISTS rounds_fought  TINYINT UNSIGNED DEFAULT NULL AFTER rounds_total,
    ADD COLUMN IF NOT EXISTS amateur        TINYINT(1) NOT NULL DEFAULT 1 AFTER rounds_fought;

CREATE TABLE IF NOT EXISTS boxing_medicals (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id         INT UNSIGNED NOT NULL,
    member_id       INT UNSIGNED NOT NULL,
    exam_date       DATE NOT NULL,
    valid_until     DATE NOT NULL,
    clearance_type  ENUM('amateur','pro','sparring_only') NOT NULL DEFAULT 'amateur',
    doctor_name     VARCHAR(150),
    notes           TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_bm_club`   (`club_id`),
    KEY `idx_bm_member` (`member_id`),
    KEY `idx_bm_valid`  (`valid_until`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
