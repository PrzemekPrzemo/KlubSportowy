-- ============================================================
-- Migracja 079_achievements.sql
--
-- System achievements / badges (gamification) dla zawodnikow.
-- Multi-tenant:
--   * achievement_catalog.club_id NULL  -> global badge (dostepny dla
--     wszystkich klubow)
--   * achievement_catalog.club_id INT  -> custom badge per klub
--   * member_achievements zawsze ma club_id NOT NULL (audyt + scoping)
--
-- Idempotentnosc: UNIQUE KEY (member_id, achievement_id) zapobiega
-- duplikatom — wielokrotne wywolanie AchievementEvaluator jest bezpieczne.
-- ============================================================
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `achievement_catalog` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id` INT UNSIGNED NULL COMMENT 'NULL = global, INT = per-klub custom',
    `code` VARCHAR(60) NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `description` VARCHAR(500) NULL,
    `category` ENUM('attendance','tournament','training','milestone','sport_specific','social','other') NOT NULL DEFAULT 'other',
    `icon` VARCHAR(80) NOT NULL DEFAULT '🏆' COMMENT 'Emoji lub bootstrap icon klasa (bi-...)',
    `rarity` ENUM('common','uncommon','rare','epic','legendary') NOT NULL DEFAULT 'common',
    `points` INT UNSIGNED NOT NULL DEFAULT 10,
    `criteria` JSON NOT NULL COMMENT 'Warunki ewaluacji, np. {"type":"tournament_place","place":1}',
    `sport_key` VARCHAR(40) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_club_code` (`club_id`, `code`),
    KEY `idx_category` (`category`),
    KEY `idx_active` (`is_active`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Katalog odznak: global (club_id NULL) + per-klub';

CREATE TABLE IF NOT EXISTS `member_achievements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id` INT UNSIGNED NOT NULL,
    `member_id` INT UNSIGNED NOT NULL,
    `achievement_id` INT UNSIGNED NOT NULL,
    `earned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `context` JSON NULL COMMENT 'Dane dodatkowe: tournament_id, count, time, etc.',
    `is_displayed` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Czy pokazac w profilu (member moze ukryc)',
    UNIQUE KEY `uniq_member_achievement` (`member_id`, `achievement_id`),
    KEY `idx_club_recent` (`club_id`, `earned_at`),
    KEY `idx_member_earned` (`member_id`, `earned_at`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`achievement_id`) REFERENCES `achievement_catalog`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Zdobyte odznaki przez zawodnikow';

-- ============================================================
-- Seed global achievements (~25 startowych odznak)
-- ============================================================
INSERT IGNORE INTO `achievement_catalog`
  (`club_id`, `code`, `name`, `description`, `category`, `icon`, `rarity`, `points`, `criteria`, `sort_order`) VALUES
-- Attendance (treningi)
(NULL, 'first_training',  'Pierwsze kroki',          'Wziąłeś udział w pierwszym treningu',                'attendance', '🌱', 'common',    10,  '{"type":"trainings_count","count":1}',     10),
(NULL, 'training_10',     'Regularne treningi',      'Ukończyłeś 10 treningów',                            'attendance', '💪', 'common',    25,  '{"type":"trainings_count","count":10}',    20),
(NULL, 'training_50',     'Wytrwałość',              '50 treningów za nami',                               'attendance', '🔥', 'uncommon',  50,  '{"type":"trainings_count","count":50}',    30),
(NULL, 'training_100',    'Setka',                   '100 treningów — godna szacunku liczba',              'attendance', '💯', 'rare',      100, '{"type":"trainings_count","count":100}',   40),
(NULL, 'training_500',    'Weteran',                 '500 treningów — legendo!',                           'attendance', '🏛️', 'epic',     250, '{"type":"trainings_count","count":500}',   50),
(NULL, 'training_1000',   'Niezniszczalny',          '1000 treningów. Po prostu wow.',                     'attendance', '⭐', 'legendary', 500, '{"type":"trainings_count","count":1000}',  60),
(NULL, 'perfect_month',   'Miesięczna konsekwencja', '100% obecności w danym miesiącu',                    'attendance', '✅', 'uncommon',  30,  '{"type":"perfect_month"}',                 70),
(NULL, 'streak_5',        'Pięciodniówka',           '5 treningów z rzędu (bez nieobecności)',             'attendance', '🚀', 'common',    20,  '{"type":"training_streak","count":5}',     75),
(NULL, 'streak_20',       'Maraton frekwencji',      '20 treningów z rzędu',                               'attendance', '⚡', 'rare',      80,  '{"type":"training_streak","count":20}',    78),
-- Tournaments (turnieje)
(NULL, 'first_tournament', 'Debiut',                  'Wziąłeś udział w pierwszym turnieju',                'tournament', '🎯', 'common',    20,  '{"type":"tournament_played"}',             100),
(NULL, 'tournament_winner','Złoty medal',             'Wygrałeś turniej',                                   'tournament', '🥇', 'rare',      150, '{"type":"tournament_place","place":1}',    110),
(NULL, 'tournament_silver','Srebrny medal',           '2. miejsce w turnieju',                              'tournament', '🥈', 'uncommon',  75,  '{"type":"tournament_place","place":2}',    120),
(NULL, 'tournament_bronze','Brązowy medal',           '3. miejsce w turnieju',                              'tournament', '🥉', 'uncommon',  50,  '{"type":"tournament_place","place":3}',    130),
(NULL, 'tournament_top10', 'Top 10',                  'Top 10 w turnieju (>10 uczestników)',                'tournament', '🎖️', 'common',   20,  '{"type":"tournament_top","n":10}',         140),
(NULL, 'tournaments_10',   'Doświadczony zawodnik',   '10 turniejów na koncie',                             'tournament', '🏅', 'rare',      75,  '{"type":"tournaments_played_count","count":10}', 150),
(NULL, 'hat_trick',        'Hat-trick',               '3 zwycięstwa turniejowe w jednym sezonie',           'tournament', '🎩', 'epic',      200, '{"type":"season_wins","count":3}',         160),
-- Milestones (jubileusze)
(NULL, 'club_anniversary_1y',  'Rok w klubie',        '1 rok członkostwa',                                  'milestone', '🎂', 'uncommon',   50,  '{"type":"membership_years","years":1}',    200),
(NULL, 'club_anniversary_5y',  '5 lat w klubie',      'Pół dekady wierności klubowi',                       'milestone', '🎂', 'rare',       200, '{"type":"membership_years","years":5}',    210),
(NULL, 'club_anniversary_10y', '10 lat w klubie',     'Dekada przynależności',                              'milestone', '👑', 'legendary',  500, '{"type":"membership_years","years":10}',   220),
(NULL, 'profile_complete',     'Pełny profil',        'Uzupełniłeś profil w 100% (email, telefon, adres)',  'milestone', '📋', 'common',     15,  '{"type":"profile_complete"}',              230),
-- Training (etapy/postępy techniczne)
(NULL, 'belt_promo_first',     'Pierwszy pas',        'Otrzymałeś pierwszy stopień / pas',                  'training',  '🥋', 'uncommon',   40,  '{"type":"belt_promotions_count","count":1}', 240),
(NULL, 'belt_promo_5',         'Mistrz stopni',       '5 promocji pasów / stopni',                          'training',  '🎓', 'epic',       180, '{"type":"belt_promotions_count","count":5}', 250),
-- Social
(NULL, 'team_player',    'Drużynowiec',               'Wygrałeś mecz drużynowy',                            'social',    '🤝', 'common',     15,  '{"type":"team_match_won"}',                300),
(NULL, 'referrer',       'Polecenie',                 'Poleciłeś nowego członka który dołączył do klubu',   'social',    '🎁', 'rare',       100, '{"type":"referrals_count","count":1}',     310),
(NULL, 'community_5',    'Ambasador klubu',           '5 osób dołączyło z Twojego polecenia',               'social',    '📣', 'epic',       200, '{"type":"referrals_count","count":5}',     320);

SET foreign_key_checks = 1;
