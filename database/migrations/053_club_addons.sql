-- ============================================================
-- Migracja 053_club_addons.sql
--
-- Q.2 — system dokupowania dodatkowych zasobów (add-ons) do planu klubu.
--
-- Klub na planie 'Klub' (limit 150 zawodników, 5 sekcji) może dokupić:
--   - +10 / +50 zawodników (boost limitu)
--   - +1 / +5 sekcji sportowych
--   - extra SMS pakiety (przyszłość)
--   - custom domena (przyszłość)
--
-- Tabele:
--   - addon_catalog       — katalog dostępnych addonow (Master Admin manage)
--   - club_addons         — aktywne subskrypcje addonow per klub
--
-- Logika:
--   effectiveLimits($clubId) = plan.max_X + SUM(active addons that boost X)
--   SportsController::enable() używa effectiveLimits zamiast bezpośrednio plan.max_sports
-- ============================================================

SET foreign_key_checks = 0;

-- ── Katalog dostępnych addonow ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `addon_catalog` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`          VARCHAR(40) NOT NULL UNIQUE,
    `name`          VARCHAR(100) NOT NULL,
    `description`   VARCHAR(255) NULL,
    `category`      ENUM('members','sports','sms','domain','support','other')
                    NOT NULL DEFAULT 'other',
    `boost_field`   VARCHAR(40) NULL COMMENT 'Pole w plan.max_X które boost-uje (np. max_members)',
    `boost_amount`  INT NULL COMMENT 'Ilość dodanego limitu (np. 10, 50, 1, 5)',
    `monthly_price` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
    `yearly_price`  DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_addon_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Q.2 — katalog dostepnych addonow do dokupienia przez klub';

-- ── Subskrypcje addonow per klub ────────────────────────────────
CREATE TABLE IF NOT EXISTS `club_addons` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`       INT UNSIGNED NOT NULL,
    `addon_id`      INT UNSIGNED NOT NULL,
    `quantity`      SMALLINT UNSIGNED NOT NULL DEFAULT 1
                    COMMENT 'Ile sztuk tego addona (np. 3x +10 zawodnikow = +30)',
    `monthly_price` DECIMAL(10,2) UNSIGNED NOT NULL COMMENT 'Snapshot ceny w momencie zakupu',
    `status`        ENUM('active','cancelled','suspended','expired') NOT NULL DEFAULT 'active',
    `valid_from`    DATE NOT NULL DEFAULT (CURRENT_DATE),
    `valid_until`   DATE NULL COMMENT 'NULL = bezterminowo (auto-renew)',
    `auto_renew`    TINYINT(1) NOT NULL DEFAULT 1,
    `notes`         TEXT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_club_addon_active` (`club_id`, `status`),
    KEY `idx_club_addon_valid`  (`valid_until`, `status`),
    FOREIGN KEY (`club_id`)  REFERENCES `clubs`(`id`)         ON DELETE CASCADE,
    FOREIGN KEY (`addon_id`) REFERENCES `addon_catalog`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Q.2 — aktywne subskrypcje addonow per klub';

-- ── Seed: 6 dostepnych addonow (PL pricing) ────────────────────
INSERT INTO `addon_catalog`
    (`code`, `name`, `description`, `category`, `boost_field`, `boost_amount`, `monthly_price`, `yearly_price`, `sort_order`)
VALUES
    ('extra_members_10',  '+10 zawodnikow',  'Dodaj 10 do limitu zawodnikow w planie', 'members', 'max_members', 10, 12.00, 120.00, 1),
    ('extra_members_50',  '+50 zawodnikow',  'Dodaj 50 do limitu zawodnikow (taniej w przeliczeniu)', 'members', 'max_members', 50, 49.00, 490.00, 2),
    ('extra_members_100', '+100 zawodnikow', 'Dodaj 100 do limitu zawodnikow (najlepsza cena/jednostke)', 'members', 'max_members', 100, 89.00, 890.00, 3),
    ('extra_sport_1',     '+1 sekcja sportowa', 'Dodaj 1 do limitu sekcji w planie', 'sports', 'max_sports', 1, 25.00, 250.00, 4),
    ('extra_sports_5',    '+5 sekcji sportowych', 'Pakiet 5 dodatkowych sekcji (-20% vs pojedynczo)', 'sports', 'max_sports', 5, 99.00, 990.00, 5),
    ('custom_domain',     'Custom domena',  'Twoja domena (klub.com) zamiast subdomeny clubdesk.pl', 'domain', NULL, NULL, 49.00, 490.00, 10)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    monthly_price = VALUES(monthly_price),
    yearly_price = VALUES(yearly_price);

SET foreign_key_checks = 1;
