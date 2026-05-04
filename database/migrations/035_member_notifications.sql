CREATE TABLE IF NOT EXISTS member_notifications (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    club_id     INT UNSIGNED NOT NULL,
    member_id   INT UNSIGNED NOT NULL,
    type        ENUM('medical_expiry','license_expiry','announcement','result','belt_promotion','general') NOT NULL DEFAULT 'general',
    title       VARCHAR(200) NOT NULL DEFAULT '',
    body        TEXT         NULL,
    link        VARCHAR(255) NULL,
    read_at     DATETIME     NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_mn_member   (member_id, read_at),
    INDEX idx_mn_club     (club_id),
    INDEX idx_mn_created  (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
