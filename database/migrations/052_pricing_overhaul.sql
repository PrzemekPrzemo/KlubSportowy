-- ============================================================
-- Migracja 052_pricing_overhaul.sql
--
-- Q.1 — restrukturyzacja planów cenowych ClubDesk po analizie konkurencji.
--
-- ZMIANY:
--   - Stare 4 plany (trial/basic/standard/premium) zostają deactivated
--   - Nowe 5 planów dopasowanych do polskiego rynku klubów sportowych
--   - Trial 14 dni (skrócony z 30 — branżowy standard)
--   - Yearly = 12× monthly × 0.83 (≈ 17% rabat za rocznie z góry)
--   - Top tier "Federacja" do związków sportowych z wieloma klubami
--
-- KONKURENCJA (analiza, brak zmian w kodzie):
--   TeamUp:    €15-€60/m-c (UK, max 250+ członków)
--   Spond:     freemium + €5/coach/m-c
--   TeamSnap:  $9.99-49/team/m-c
--   Klubduden: ~50-200 zł/m-c (PL)
--   Sportlyzer:€19-99/m-c
--
-- WYRÓŻNIKI ClubDesk vs konkurencja:
--   - 49 dyscyplin (vs 1-3 u większości)
--   - 4 polskie bramki płatności (P24/PayU/Tpay/Stripe)
--   - WADA anti-doping module
--   - Cross-club identity (zawodnik w wielu klubach)
--   - Polskie federacje (PZPN, PZKosz, PZPS, PZJ, PZN, etc.)
-- ============================================================

SET foreign_key_checks = 0;

-- 1. Deactivate stare plany (zachowujemy historię — kluby z subskrypcją zostają)
UPDATE subscription_plans SET is_active = 0
 WHERE code IN ('trial','basic','standard','premium');

-- 2. Wstaw nowe plany (idempotent — ON DUPLICATE KEY na code)
INSERT INTO subscription_plans (code, name, max_members, max_sports,
                                price_monthly, price_yearly, features, sort_order, is_active)
VALUES
  -- ──────────────── DARMOWY TRIAL ────────────────────
  ('trial_v2', 'Trial — 14 dni',
    30, 1,
    0.00, 0.00,
    JSON_OBJECT(
        'sms', false, 'api', false, 'backup', false,
        'custom_css', false, 'white_label', false,
        'gateways', 'manual', 'support', 'community',
        'trial_days', 14, 'badge', 'TRIAL',
        'description', 'Bezpłatny okres próbny. 30 zawodników, 1 sekcja sportowa.'
    ),
    1, 1),

  -- ──────────────── STARTER (mała szkółka, 1 sport) ──
  ('starter', 'Starter',
    50, 1,
    39.00, 390.00,
    JSON_OBJECT(
        'sms', false, 'api', false, 'backup', true,
        'custom_css', false, 'white_label', false,
        'gateways', 'p24,payu,tpay', 'support', 'email',
        'description', 'Małe szkółki sportowe — do 50 zawodników, 1 sekcja.'
    ),
    2, 1),

  -- ──────────────── KLUB (najpopularniejsza opcja) ───
  ('club', 'Klub',
    150, 5,
    89.00, 890.00,
    JSON_OBJECT(
        'sms', true, 'api', false, 'backup', true,
        'custom_css', true, 'white_label', false,
        'gateways', 'p24,payu,tpay,stripe',
        'medical', true, 'compliance', true,
        'support', 'email_priority',
        'badge', 'NAJPOPULARNIEJSZY',
        'description', 'Klub sportowy z kilkoma sekcjami — do 150 zawodników, 5 sekcji, SMS, custom branding.'
    ),
    3, 1),

  -- ──────────────── MULTI-SPORT (duży klub, 15 sekcji) ─
  ('multi_sport', 'Multi-Sport',
    500, 15,
    179.00, 1790.00,
    JSON_OBJECT(
        'sms', true, 'api', true, 'backup', true,
        'custom_css', true, 'white_label', false,
        'gateways', 'p24,payu,tpay,stripe',
        'medical', true, 'compliance', true,
        'analytics', true, 'reports_pdf', true,
        'support', 'email_priority,phone',
        'description', 'Duży klub z wieloma dyscyplinami — do 500 zawodników, 15 sekcji, API, telefon.'
    ),
    4, 1),

  -- ──────────────── ENTERPRISE (akademie, większe związki) ──
  ('enterprise', 'Enterprise',
    NULL, NULL,
    349.00, 3490.00,
    JSON_OBJECT(
        'sms', true, 'api', true, 'backup', true,
        'custom_css', true, 'white_label', true,
        'custom_domain', true, 'dedicated_smtp', true,
        'gateways', 'all',
        'medical', true, 'compliance', true,
        'analytics', true, 'reports_pdf', true,
        'support', 'dedicated_account_manager',
        'description', 'Akademie sportowe i duże związki — bez limitu, white-label, dedykowane wsparcie.'
    ),
    5, 1),

  -- ──────────────── FEDERACJA (związek z wieloma klubami) ──
  ('federation', 'Federacja',
    NULL, NULL,
    0.00, 0.00,
    JSON_OBJECT(
        'sms', true, 'api', true, 'backup', true,
        'custom_css', true, 'white_label', true,
        'custom_domain', true, 'dedicated_smtp', true,
        'gateways', 'all',
        'medical', true, 'compliance', true,
        'analytics', true, 'reports_pdf', true,
        'multi_club_admin', true, 'sso', true,
        'support', 'sla,dedicated_team',
        'pricing_model', 'custom_quote',
        'description', 'Polskie związki sportowe i sieci klubów — wycena indywidualna, SSO, SLA.'
    ),
    6, 1)
ON DUPLICATE KEY UPDATE
    name          = VALUES(name),
    max_members   = VALUES(max_members),
    max_sports    = VALUES(max_sports),
    price_monthly = VALUES(price_monthly),
    price_yearly  = VALUES(price_yearly),
    features      = VALUES(features),
    sort_order    = VALUES(sort_order),
    is_active     = VALUES(is_active);

SET foreign_key_checks = 1;
