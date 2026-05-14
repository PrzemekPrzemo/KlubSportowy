-- ============================================================
-- Migracja 065_gcal_feature_flag.sql
--
-- Dodaje feature flag `google_calendar_sync` do katalogu flag.
-- Wymaga uruchomionej migracji 056_feature_flags.sql.
--
-- Plan default:
--   starter      → false  (poza pakietem)
--   club         → false  (poza pakietem, ad-hoc override możliwe)
--   multi_sport  → true
--   enterprise   → true
--   federation   → true
--   trial_v2     → false
-- ============================================================

INSERT INTO `feature_flags_catalog`
    (`code`, `name`, `description`, `category`, `default_in_plan`, `sort_order`)
VALUES
    ('google_calendar_sync',
     'Synchronizacja z Google Calendar',
     'Dwukierunkowy sync wydarzen klubu z Google Calendar trenerow/czlonkow (OAuth2 + Calendar API v3).',
     'integration',
     JSON_OBJECT('starter', false, 'club', false, 'multi_sport', true, 'enterprise', true, 'federation', true, 'trial_v2', false),
     110)
ON DUPLICATE KEY UPDATE
    `name`            = VALUES(`name`),
    `description`     = VALUES(`description`),
    `category`        = VALUES(`category`),
    `default_in_plan` = VALUES(`default_in_plan`),
    `sort_order`      = VALUES(`sort_order`);
