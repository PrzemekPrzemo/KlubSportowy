# Split payments — model rozliczeń platforma ↔ klub

## TL;DR

Każdy klub ma swoje własne konto merchanta (Stripe Connect Express). Kwota
płatności online trafia bezpośrednio na konto klubu, ClubDesk
(Sendormeco Holding Sp. z o.o.) automatycznie potrąca platform fee
(domyślnie 2%) z każdej transakcji.

Nie ma ręcznego rozliczania, kompensat ani osobnych faktur za prowizję —
wszystko odbywa się on-the-fly podczas autoryzacji płatności.

## Aktorzy

- **Klub** — sportowa organizacja korzystająca z ClubDesk. Jest merchantem
  (sprzedawcą) wobec swoich członków.
- **Członek** — kupujący (płatnik) — opłaca składki/wpisowe.
- **ClubDesk / Sendormeco** — operator platformy SaaS. Pobiera platform
  fee jako wynagrodzenie za udostępnienie infrastruktury.
- **Stripe** — dostawca infrastruktury split payments (Stripe Connect).
- **Przelewy24** — alternatywny dostawca, dziś **bez split** (tymczasowo
  out-of-band fee — patrz niżej).

## Flow Stripe Connect (live)

```
Członek                  Stripe Checkout              Klub (Connect)        ClubDesk
   │                          │                             │                  │
   │── pay 100 PLN ──────────▶│                             │                  │
   │                          │── 98 PLN (transfer) ──────▶│                  │
   │                          │── 2 PLN (application_fee) ──────────────────▶│
   │                          │                             │                  │
   │                          │── webhook checkout.completed ──────────────────▶│
   │                                                                         │
   │                                            payout (cycle weekly) ──▶ bank klubu
```

## Onboarding klubu

1. Klub admin → **Konto rozliczeń ClubDesk** w sidebarze.
2. Klik "Rozpocznij onboarding Stripe" → POST `/club/platform-payment/onboard`
   - jeśli brak konta → `StripeConnectAdapter::createConnectAccount()`
     (Express, country=PL, capabilities card_payments + transfers).
   - generujemy `account_links` (`type=account_onboarding`).
   - redirect do Stripe-hosted UI.
3. Klub wypełnia dane firmy (KYC), dane banku, weryfikacja tożsamości.
4. Stripe redirect z powrotem → GET `/club/platform-payment/return`
   - `getAccountStatus()` → upsert `kyc_status`, `charges_enabled`,
     `payouts_enabled`, `onboarding_complete`.

Po `charges_enabled=1` PaymentGateway przełącza checkout do wariantu
`createCheckoutWithFee()` (zamiast zwykłego StripeAdapter).

## Platform fee rules

Tabela `platform_fee_rules`. Hierarchia od najbardziej do najmniej
specyficznej:

1. `scope='club_override'` z `club_id=X` — wyjątek negocjowany dla klubu.
2. `scope='plan'` z `plan_code=YYY` — stawka per-plan (np. enterprise: 1%).
3. `scope='global'` — domyślnie 2% (seed z migracji 092).

Każda reguła ma `effective_from` / `effective_until` — okresy efektywności.
Reguła musi mieć `active=1` i być w okresie obowiązywania w danej chwili.

**Math** (PlatformFeeCalculator):

```
fee = round(gross * fee_percent / 100, half_up) + fee_fixed_cents
fee = clamp(fee, min_fee_cents, max_fee_cents ?? +∞)
fee = min(fee, gross)   # nigdy więcej niż transakcja
```

## Webhook → ledger

Po `checkout.session.completed` (Stripe Connect platform webhook) zapisujemy
wpis do `platform_fee_charges`:

| Kolumna | Wartość |
|---|---|
| club_id | z `metadata.club_id` |
| provider | `stripe_connect` |
| transaction_id | `payment_intent_id` |
| gross_amount_cents | `amount_total` |
| platform_fee_cents | `application_fee_amount` |
| club_net_amount_cents | gross − fee |

Raport revenue: `/admin/platform/payments/charges` — filtry by date/club.

## Cykl wypłat (payouts)

Stripe Connect Express domyślnie wypłaca raz w tygodniu. Klub może to
zmienić w panelu Stripe Express (`https://connect.stripe.com/express`)
na daily / monthly / manual.

Pieniądze są na koncie Stripe klubu od razu po autoryzacji, ale do banku
trafiają po cyklu payout (T+2 w Polsce dla nowych kont, T+0/+1 dla
zweryfikowanych).

## Przelewy24 — status

Marketplace Multi-Account P24 wymaga osobnej umowy partnerskiej między
Sendormeco Holding a Przelewy24. Do czasu jej zawarcia:

- klub używa zwykłego P24 (przez `Przelewy24Adapter` w `club_payment_gateways`),
- płatności idą bezpośrednio na konto P24 klubu,
- platform fee ClubDesk jest rozliczana **out-of-band** — comiesięczna
  faktura SaaS, generowana razem z `billing_invoices`.

Komunikat w UI: `P24MarketplaceAdapter::PARTNERSHIP_NOTICE`.

## Klucze API

Wymagana konfiguracja w `config/app.php`:

```php
'stripe_platform_secret' => env('STRIPE_PLATFORM_SECRET', ''),
```

To jest klucz **platformy** ClubDesk (sk_live_…), NIE klucz klubu.
Klub nie podaje żadnych kluczy Stripe — onboarding tworzy mu konto.

## Multi-tenant

- `platform_payment_accounts.club_id` z FK CASCADE.
- `PlatformFeeCalculator` resolwuje regułę zawsze z `clubId` parametru
  (nie z sesji) — bezpieczne dla shared kernel jobs / webhooks.
- View dla klubu (`ClubPlatformPaymentsController`) wymaga `requireClubContext()`
  i `requireRole(['zarzad','admin'])`.
- View super admin (`AdminPlatformPaymentsController`) wymaga
  `requireSuperAdmin()`.
