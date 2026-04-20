CREATE TABLE IF NOT EXISTS sport_module_flags (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sport_key          VARCHAR(50)  NOT NULL UNIQUE,
    is_globally_active TINYINT(1)   NOT NULL DEFAULT 1,
    updated_at         DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (sport_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
