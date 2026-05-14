-- ============================================================
-- Migracja 056_feature_flags.sql
--
-- Per-klub feature flags — fundament pod realne różnice w pakietach
-- cenowych (Basic/Pro/Enterprise → starter/club/multi_sport/enterprise).
--
-- UWAGA: To NIE jest powielanie systemu addons (migracja 053).
--   - addons       = boostery limitów ILOŚCIOWYCH (+10 zawodników, +5 sekcji)
--   - feature flags = boolean WŁĄCZ/WYŁĄCZ dla funkcjonalności
--                     (pdf_export, sms_notifications, whitelabel, api_access...)
--
-- Architektura:
--   feature_flags_catalog    — master katalog wszystkich możliwych flag
--   club_feature_overrides   — override per klub (opcjonalny, np. trial Pro
--                              feature dla Basic clienta lub wyłączenie
--                              feature mimo planu)
--
-- Logika `Feature::enabled($code, $clubId)`:
--   1) Sprawdź override w `club_feature_overrides`
--      (jeśli istnieje i nie wygasł — użyj jego `enabled`)
--   2) Inaczej pobierz default z `feature_flags_catalog.default_in_plan`
--      mapowane przez plan klubu (subscription_plans.code)
--   3) Flaga nie istnieje w catalog → false (fail closed)
-- ============================================================

SET foreign_key_checks = 0;

-- ── Master katalog flag (Master Admin manage) ─────────────────
CREATE TABLE IF NOT EXISTS `feature_flags_catalog` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code`             VARCHAR(60)  NOT NULL UNIQUE,
    `name`             VARCHAR(120) NOT NULL,
    `description`      VARCHAR(500) NULL,
    `category`         VARCHAR(40)  NOT NULL DEFAULT 'general',
    `default_in_plan`  JSON         NOT NULL
                       COMMENT 'Mapa plan_code => bool, np. {"starter":false,"club":true,"multi_sport":true,"enterprise":true}',
    `is_active`        TINYINT(1)   NOT NULL DEFAULT 1,
    `sort_order`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ff_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Master katalog feature flags — boolean włącz/wyłącz per plan';

-- ── Per-klub override (opcjonalny) ────────────────────────────
CREATE TABLE IF NOT EXISTS `club_feature_overrides` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `club_id`       INT UNSIGNED NOT NULL,
    `feature_code`  VARCHAR(60)  NOT NULL,
    `enabled`       TINYINT(1)   NOT NULL,
    `reason`        VARCHAR(255) NULL
                    COMMENT 'Notatka admina dlaczego override (np. trial, promocja, ad-hoc)',
    `expires_at`    DATETIME     NULL
                    COMMENT 'Auto-expire override; NULL = trwale (do ręcznego usunięcia)',
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by`    INT UNSIGNED NULL
                    COMMENT 'users.id admina który dokonał override',
    UNIQUE KEY `uniq_club_feature` (`club_id`, `feature_code`),
    KEY `idx_expires` (`expires_at`),
    KEY `idx_feature` (`feature_code`),
    FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Override flag per-klub (Master Admin tool: trial Pro features dla Basic, itd.)';

-- ── Seed: startowe flagi ──────────────────────────────────────
-- Mapowanie planów (z migracji 052_pricing_overhaul.sql):
--   starter      = "Basic"   (mała szkółka)
--   club         = "Pro"     (najpopularniejszy)
--   multi_sport  = "Pro+"    (duży klub)
--   enterprise   = "Enterprise"
--   federation   = "Enterprise++"
--   trial_v2     = trial (14 dni, max funkcje testowo dostępne na życzenie)
INSERT INTO `feature_flags_catalog`
    (`code`, `name`, `description`, `category`, `default_in_plan`, `sort_order`)
VALUES
    ('advanced_rankings',
     'Zaawansowane rankingi (Elo)',
     'Auto-kalkulowane rankingi sportowe na podstawie wyników (Elo/Glicko).',
     'sport',
     JSON_OBJECT('starter', false, 'club', true,  'multi_sport', true,  'enterprise', true, 'federation', true,  'trial_v2', false),
     10),

    ('pdf_export',
     'Eksport PDF (zaświadczenia, FV, raporty)',
     'Generowanie dokumentów PDF (raporty zawodników, finansowe, certyfikaty).',
     'documents',
     JSON_OBJECT('starter', false, 'club', true,  'multi_sport', true,  'enterprise', true, 'federation', true,  'trial_v2', true),
     20),

    ('sms_notifications',
     'Powiadomienia SMS',
     'Wysyłka SMS przez SMSAPI/Twilio (treningi, składki, ogłoszenia).',
     'communication',
     JSON_OBJECT('starter', false, 'club', true,  'multi_sport', true,  'enterprise', true, 'federation', true,  'trial_v2', false),
     30),

    ('whitelabel_branding',
     'Whitelabel (custom kolory + logo + domena)',
     'Pełna customizacja brandingu — własne kolory, logo, custom CSS, brak brandingu ClubDesk.',
     'branding',
     JSON_OBJECT('starter', false, 'club', false, 'multi_sport', false, 'enterprise', true, 'federation', true,  'trial_v2', false),
     40),

    ('api_access',
     'Dostęp do REST API',
     'Klucze API dla integracji z systemami zewnętrznymi (CRM, ERP, custom dashboards).',
     'integration',
     JSON_OBJECT('starter', false, 'club', false, 'multi_sport', true,  'enterprise', true, 'federation', true,  'trial_v2', false),
     50),

    ('mobile_app',
     'Aplikacja mobilna',
     'Dostęp członków klubu przez aplikację mobilną (iOS/Android).',
     'mobile',
     JSON_OBJECT('starter', false, 'club', true,  'multi_sport', true,  'enterprise', true, 'federation', true,  'trial_v2', true),
     60),

    ('federation_export',
     'Eksport do federacji sportowych',
     'Auto-eksport zawodników do PZPN/PZKosz/PZPS/PZJ/PZN i innych polskich federacji.',
     'integration',
     JSON_OBJECT('starter', false, 'club', false, 'multi_sport', false, 'enterprise', true, 'federation', true,  'trial_v2', false),
     70),

    ('live_score',
     'Live score (real-time)',
     'Real-time wyniki na publicznej stronie klubu (mecze, turnieje, transmisje).',
     'sport',
     JSON_OBJECT('starter', false, 'club', false, 'multi_sport', true,  'enterprise', true, 'federation', true,  'trial_v2', false),
     80)
ON DUPLICATE KEY UPDATE
    `name`            = VALUES(`name`),
    `description`     = VALUES(`description`),
    `category`        = VALUES(`category`),
    `default_in_plan` = VALUES(`default_in_plan`),
    `sort_order`      = VALUES(`sort_order`);

SET foreign_key_checks = 1;
