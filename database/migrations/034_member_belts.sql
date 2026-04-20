CREATE TABLE IF NOT EXISTS member_belts (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    club_id         INT UNSIGNED    NOT NULL,
    member_id       INT UNSIGNED    NOT NULL,
    club_sport_id   INT UNSIGNED    NOT NULL,
    belt_color      VARCHAR(50)     NOT NULL DEFAULT '',
    belt_level      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=biały, rosnąco wg systemu danej sztuki',
    exam_date       DATE            NOT NULL,
    examiner        VARCHAR(120)    NULL,
    location        VARCHAR(150)    NULL,
    notes           TEXT            NULL,
    created_by      INT UNSIGNED    NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_mb_member    (member_id),
    INDEX idx_mb_club_sport (club_id, club_sport_id),
    INDEX idx_mb_date      (exam_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
