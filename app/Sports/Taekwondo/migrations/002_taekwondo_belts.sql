-- Taekwondo: belt grading system (gup/dan) + weight_class for results

CREATE TABLE IF NOT EXISTS taekwondo_belts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id      INT UNSIGNED NOT NULL,
    member_id    INT UNSIGNED NOT NULL,
    belt_level   ENUM('10gup','9gup','8gup','7gup','6gup','5gup','4gup','3gup','2gup','1gup',
                      '1dan','2dan','3dan','4dan','5dan','6dan','7dan','8dan','9dan') NOT NULL,
    granted_date DATE         NOT NULL,
    examiner     VARCHAR(120),
    location     VARCHAR(150),
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id)   REFERENCES clubs(id)   ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE taekwondo_results
    ADD COLUMN IF NOT EXISTS weight_class VARCHAR(10) DEFAULT NULL AFTER age_category;
