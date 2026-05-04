-- Fencing: profil szermierza + ranking per broń
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS fencing_fencers (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id            INT UNSIGNED NOT NULL,
    member_id          INT UNSIGNED NOT NULL,
    fie_id             VARCHAR(30) NULL COMMENT 'Federation Internationale d''Escrime ID',
    primary_weapon     ENUM('foil','epee','sabre') NOT NULL DEFAULT 'foil',
    laterality         ENUM('praworęczny','leworęczny','oburęczny') DEFAULT 'praworęczny',
    ranking_points     INT DEFAULT 0,
    height_cm          SMALLINT UNSIGNED NULL,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_ff` (`club_id`, `member_id`),
    KEY `idx_ff_club`   (`club_id`),
    KEY `idx_ff_weapon` (`primary_weapon`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
