-- Generic sport license tracking for all sports

CREATE TABLE IF NOT EXISTS member_sport_licenses (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id        INT UNSIGNED NOT NULL,
    member_id      INT UNSIGNED NOT NULL,
    sport_key      VARCHAR(40)  NOT NULL,
    license_number VARCHAR(80)  NOT NULL,
    federation     VARCHAR(60),
    license_class  VARCHAR(50),
    valid_from     DATE         NOT NULL,
    valid_to       DATE,
    status         ENUM('active','expired','suspended') NOT NULL DEFAULT 'active',
    notes          TEXT,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_club_member (club_id, member_id),
    INDEX idx_sport_key   (club_id, sport_key),
    INDEX idx_valid_to    (valid_to),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
