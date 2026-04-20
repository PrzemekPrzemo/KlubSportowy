CREATE TABLE IF NOT EXISTS association_meetings (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id         INT UNSIGNED NOT NULL,
    meeting_type    ENUM('walne','zarząd','komisja_rewizyjna','nadzwyczajne') NOT NULL DEFAULT 'zarząd',
    meeting_date    DATE NOT NULL,
    location        VARCHAR(200) DEFAULT NULL,
    quorum_reached  TINYINT(1) NOT NULL DEFAULT 0,
    agenda          TEXT DEFAULT NULL,
    protocol_path   VARCHAR(255) DEFAULT NULL,
    created_by      INT UNSIGNED DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (meeting_date),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS association_votes (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    meeting_id        INT UNSIGNED NOT NULL,
    club_id           INT UNSIGNED NOT NULL,
    resolution_number VARCHAR(30) NOT NULL,
    title             VARCHAR(255) NOT NULL,
    content           TEXT DEFAULT NULL,
    vote_yes          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    vote_no           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    vote_abstain      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    result            ENUM('przyjęta','odrzucona','nieważna') NOT NULL DEFAULT 'przyjęta',
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (meeting_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (meeting_id) REFERENCES association_meetings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS association_board_members (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    club_id     INT UNSIGNED NOT NULL,
    member_id   INT UNSIGNED NOT NULL,
    role        ENUM('prezes','wiceprezes','sekretarz','skarbnik','członek_zarządu','komisja_rewizyjna','przewodniczący_kr') NOT NULL,
    term_start  DATE NOT NULL,
    term_end    DATE DEFAULT NULL,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (club_id),
    INDEX (member_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
