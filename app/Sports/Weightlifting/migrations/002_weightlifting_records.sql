-- Weightlifting: rekordy osobiste / klubowe
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS weightlifting_records (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id       INT UNSIGNED NOT NULL,
    member_id     INT UNSIGNED NOT NULL,
    record_type   ENUM('club','personal','national') NOT NULL DEFAULT 'personal',
    lift          ENUM('rwanie','podrzut','dwubój') NOT NULL,
    weight_class  VARCHAR(15) NOT NULL,
    value_kg      DECIMAL(5,1) NOT NULL,
    set_at        DATE NOT NULL,
    event_name    VARCHAR(200) NULL,
    notes         TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_wr_club`   (`club_id`),
    KEY `idx_wr_member` (`member_id`),
    KEY `idx_wr_type`   (`record_type`, `lift`, `weight_class`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
