-- ============================================================
-- Migracja 054_remove_custom_domain.sql
--
-- Decyzja produktowa: rezygnujemy z custom_domain feature.
-- Wszystkie kluby pozostają na subdomenie clubdesk.pl.
--
-- Zmiany:
--   1. Deactivate addon 'custom_domain' w addon_catalog
--   2. Anuluj wszystkie aktywne subskrypcje custom_domain klubów
--   3. Wyczyść feature flag custom_domain z planów (enterprise, federation)
--
-- Idempotent — każdy fragment safe do re-run.
-- ============================================================

SET foreign_key_checks = 0;

-- 1. Deactivate w katalogu (zachowujemy historię, nie usuwamy rekordu)
UPDATE addon_catalog
   SET is_active = 0
 WHERE code = 'custom_domain';

-- 2. Anuluj aktywne subskrypcje custom_domain (jeśli ktoś wykupił)
UPDATE club_addons ca
  JOIN addon_catalog ac ON ac.id = ca.addon_id
   SET ca.status = 'cancelled',
       ca.auto_renew = 0,
       ca.notes = CONCAT(COALESCE(ca.notes, ''), ' [auto-cancelled by mig 054 — feature removed]')
 WHERE ac.code = 'custom_domain'
   AND ca.status IN ('active', 'suspended');

-- 3. Usuń feature flag z JSON-a planów (enterprise + federation)
--    JSON_REMOVE jest idempotentny — gdy klucz nie istnieje, wynik = bez zmiany
UPDATE subscription_plans
   SET features = JSON_REMOVE(features, '$.custom_domain')
 WHERE features IS NOT NULL
   AND JSON_EXTRACT(features, '$.custom_domain') IS NOT NULL;

SET foreign_key_checks = 1;
