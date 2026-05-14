-- ============================================================
-- Migracja 063_inpost_feature_flag.sql
--
-- Dodaje feature flags pod nowe (post-056) funkcjonalnosci, ktore
-- powinny byc gate'owane przez Feature helper:
--   - inpost_shipping   — tworzenie przesylek InPost ShipX (F.6)
--   - cross_sport_stats — dashboard aktywnosci czlonka cross-sport
--
-- Domyslne mapowanie planow zgodne z konwencja innych flag z 056:
--   starter / club / multi_sport / enterprise / federation / trial_v2
-- ============================================================

INSERT IGNORE INTO `feature_flags_catalog`
    (`code`, `name`, `description`, `category`, `default_in_plan`, `sort_order`)
VALUES
    ('inpost_shipping',
     'Wysylka InPost',
     'Tworzenie przesylek (paczkomat/kurier) przez InPost ShipX z poziomu karty czlonka.',
     'integration',
     JSON_OBJECT('starter', false, 'club', false, 'multi_sport', true,  'enterprise', true, 'federation', true,  'trial_v2', false),
     90),

    ('cross_sport_stats',
     'Statystyki cross-sport',
     'Dashboard aktywnosci czlonka po wszystkich sportach (frekwencja, wyniki, treningi).',
     'sport',
     JSON_OBJECT('starter', false, 'club', true,  'multi_sport', true,  'enterprise', true, 'federation', true,  'trial_v2', false),
     100);
