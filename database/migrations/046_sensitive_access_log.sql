-- S4: Audit log dostępu do danych wrażliwych (RODO art. 30)
-- Loguje kto, kiedy, z jakiego IP przeglądał dane medical/anti_doping/
-- body_metrics/emergency_contacts/minor_consents

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS sensitive_access_log (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NULL,
    member_id    INT UNSIGNED NULL COMMENT 'którego zawodnika dane oglądane',
    data_type    ENUM(
        'medical',
        'anti_doping',
        'body_metrics',
        'emergency_contacts',
        'minor_consent',
        'boxing_medical'
    ) NOT NULL,
    action       ENUM('view','list','edit','delete','export') NOT NULL DEFAULT 'view',
    context      VARCHAR(255) NULL COMMENT 'np. URL, nazwa widoku',
    ip_address   VARCHAR(45) NULL,
    user_agent   VARCHAR(255) NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sal_club_date`  (`club_id`, `created_at`),
    KEY `idx_sal_user`       (`user_id`),
    KEY `idx_sal_member`     (`member_id`),
    KEY `idx_sal_type`       (`data_type`, `action`),
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE SET NULL,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
