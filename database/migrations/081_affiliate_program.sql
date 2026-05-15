-- 081: Affiliate / referral program — cross-tenant relation.
--
-- Klub-referrer dostaje unikatowy kod (`KLUB-AB12CD`). Polecony klub
-- moze wpisac go podczas onboardingu (`/trial?ref=...` lub recznie).
-- Gdy polecony klub wykupi platny plan (subscription.status='active'),
-- referrer dostaje reward (discount/months_free/credit).
--
-- Anti-abuse:
--   * UNIQUE uniq_referred — jeden klub moze byc polecony tylko raz
--   * UNIQUE uniq_club     — jeden klub ma jeden aktywny kod
--   * self-referral blokowany w warstwie aplikacji (referrer != referred)

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `club_referral_codes` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL COMMENT 'Klub-referrer',
    code VARCHAR(20) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    times_used INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_code (code),
    UNIQUE KEY uniq_club (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Unikalny kod afiliacyjny per klub';

CREATE TABLE IF NOT EXISTS `club_referrals` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_club_id INT UNSIGNED NOT NULL,
    referred_club_id INT UNSIGNED NOT NULL,
    referral_code VARCHAR(20) NOT NULL,
    status ENUM('pending','qualified','paid','expired','cancelled') NOT NULL DEFAULT 'pending',
    referred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    qualified_at DATETIME NULL COMMENT 'Gdy klub przeszedl trial -> paid plan',
    paid_at DATETIME NULL COMMENT 'Gdy reward wyplacony',
    reward_type ENUM('discount','months_free','credit') NOT NULL DEFAULT 'discount',
    reward_value DECIMAL(10,2) NULL COMMENT 'Procent dla discount, miesiace dla months_free, PLN dla credit',
    reward_applied TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    UNIQUE KEY uniq_referred (referred_club_id),
    KEY idx_referrer_status (referrer_club_id, status),
    FOREIGN KEY (referrer_club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cross-tenant relacja: kto polecil kogo';

CREATE TABLE IF NOT EXISTS `referral_rewards_config` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    reward_type ENUM('discount','months_free','credit') NOT NULL DEFAULT 'discount',
    reward_value DECIMAL(10,2) NOT NULL,
    min_paid_months INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Ile miesiecy referred musi placic zeby reward sie zaktywowal',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    valid_from DATE NULL,
    valid_until DATE NULL,
    max_per_referrer INT UNSIGNED NULL COMMENT 'NULL = bez limitu',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Konfiguracja rewardow programu polecen';

-- Lekka tabelka kredytow dla typu `credit` — uzywana jako fallback kasy
-- (1 wiersz per klub; aktualny stan balansu w PLN). FK luzne, bo niektore
-- klucze moga byc zarzadzane w innych miejscach.
CREATE TABLE IF NOT EXISTS `club_credits` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    club_id INT UNSIGNED NOT NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_club (club_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Balans kredytow klubu (np. z referral rewards)';

-- Default reward: 20% off na nastepny miesiac za polecenie ktore wykupi platny plan.
INSERT IGNORE INTO `referral_rewards_config`
    (name, description, reward_type, reward_value, min_paid_months, is_active)
VALUES
    ('Standard 20% rabat',
     'Polec klub i otrzymaj 20% rabatu na nastepny miesiac swojego planu — gdy polecony klub wykupi platny plan na min 1 miesiac.',
     'discount', 20.00, 1, 1);

-- Email event catalog: dwa nowe eventy do tabeli z migracji 069.
INSERT IGNORE INTO `email_event_catalog`
    (`code`, `name`, `description`, `category`, `default_subject`, `default_body`, `available_variables`, `sort_order`)
VALUES
('referral_pending', 'Nowe polecenie (pending)',
 'Wysylane do referrera gdy ktos zarejestrowal sie przez jego kod',
 'referrals',
 'Klub {{referred.name}} zaczal rejestracje przez Twoj kod',
 'Czesc!\n\nKtos wlasnie zarejestrowal nowy klub ({{referred.name}}) korzystajac z Twojego kodu polecajacego {{referral.code}}.\n\nGdy {{referred.name}} przejdzie na platny plan, otrzymasz nagrode opisana w regulaminie programu polecen.\n\nDziekujemy za promowanie ClubDesk!',
 '["referral.code","referred.name","reward.value","reward.type"]',
 200),
('referral_qualified', 'Polecenie aktywowane',
 'Wysylane do referrera gdy polecony klub wykupil platny plan i reward zostal aktywowany',
 'referrals',
 'Twoja nagroda jest aktywna - {{reward.value}}% rabatu',
 'Czesc!\n\nSwietna wiadomosc — klub {{referred.name}}, ktory poleciles, wykupil platny plan.\n\nTwoja nagroda ({{reward.value}} {{reward.type}}) zostala wlasnie zaktywowana na Twoim koncie.\n\nDziekujemy za polecenie!',
 '["referral.code","referred.name","reward.value","reward.type"]',
 210);

SET foreign_key_checks = 1;
