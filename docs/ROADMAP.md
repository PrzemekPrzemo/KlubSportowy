# ClubDesk — Roadmap funkcjonalności (gap analysis)

Dokument bazuje na audycie kodu (maj 2026) i odpowiada na zadanie Todoist
„Dokończyć pełną funkcjonalność ClubDesk per sport i per klub" — krok zerowy:
spisać konkretną listę brakujących funkcjonalności.

## TL;DR — co już mamy

ClubDesk jest dalej posunięty niż wynika z taska:

- **Multi-tenant**: `ClubContext`, `ClubScopedModel`, subdomain routing — działa
- **Multi-sport plugin**: 48 sportów z manifestami; 6 ma pełny MVP (Football,
  Basketball, Volleyball, Shooting, Rollerskating, Athletics)
- **RBAC + 2FA + audit log + szyfrowanie wrażliwych pól (AES-256-GCM)** — gotowe
- **Stripe (z webhookami) + email queue + SMS (SMSAPI/Twilio) + FCM push** — wdrożone
- **i18n PL+EN** — strings są, jakość do weryfikacji
- **REST API v1 + iCal + webhooks wychodzące** — działają
- **Schema**: 80 tabel, kompletne pokrycie domeny

Nie startujemy „od zera". Roadmapa to **domykanie i poszerzanie**.

---

## P0 — krytyczne dla rynku PL (revenue blockers)

### 1. Polskie bramki płatności (Przelewy24, TPay, PayU)
**Stan:** tabela `club_payment_gateways` ma kolumny, ale brak Controllerów/flow.
**Co trzeba:**
- `Przelewy24PaymentService` — init transaction, webhook verify, status check
- `TPayPaymentService` — j.w.
- `PayUPaymentService` — j.w.
- Return/cancel URLs + error mapping
- Per-klub wybór bramek w `AdminClubConfig`
- Testy integracji (sandbox)

**Argument:** Stripe = ~5% rynku PL. Bez P24/TPay/PayU sprzedaż klubom w PL jest twarda.

### 2. Auto-rankingi turniejowe
**Stan:** `SportRankingsController` istnieje, ale brak logiki kalkulacji.
**Co trzeba:**
- Engine rankingów per sport (Elo / punkty ligowe / czas — w zależności od typu)
- Cron przeliczający rankingi po wynikach
- Cache + invalidation
- Endpoint API + widok publiczny

**Argument:** kluby chcą leaderboardy publiczne — to argument sprzedażowy.

---

## P1 — domknięcie multi-sport (per-sport)

### 3. Per-sport migracje dla 43 sportów stub
**Stan:** 6 sportów ma własne tabele (Football, Basketball, Volleyball, Shooting,
Rollerskating, Athletics). Pozostałe 43 mają pusty folder `migrations/`.
**Co trzeba:** kategoryzacja sportów i wspólne migracje dla grup:

- **Sporty walki (Judo, Karate, Boks, Taekwondo, BJJ)**: kategorie wagowe, stopnie
  (kyu/dan/pasy), kontuzje, wagi przed walką
- **Sporty drużynowe pozostałe (Rugby, Hokej trawa, Futsal)**: składy, mecze,
  statystyki — wzorować na Football
- **Sporty raketkowe (Tenis, Squash, Padel, Badminton, TT)**: pojedynki, sety,
  drabinka turniejowa
- **Sporty wodne (Pływanie, WP, Kajakarstwo)**: czasy, dyscypliny, baseny
- **Sporty siłowe (Podnoszenie, Strongman, Crossfit)**: wagi, próby
- **E-sport**: gry, drużyny, replay URLs

**Decyzja architektoniczna:** wspólne tabele per-grupa zamiast 43 osobnych zestawów.

### 4. Per-sport export do federacji
**Stan:** PZSS scraping i PZPN API częściowo. Brak wspólnego interfejsu.
**Co trzeba:**
- Kontrakt `FederationExporter` (interface) w `app/Helpers/Federations/`
- Implementacje per federacja, z mapowaniem pól członka → format federacji
- Cron + manualny trigger w admin panelu
- Audit kto kiedy co wysłał

### 5. Sport-specific views w module (vs centralny `app/Views/`)
**Stan:** widoki sportów są centralnie w `app/Views/football/`, `basketball/` itd.
Module ma tylko manifest + Controllers.
**Co trzeba:** przenieść do `app/Sports/<Sport>/views/` + loader. Dodanie nowego
sportu nie powinno wymagać dotykania centralnego `Views/`.

### 6. Cross-sport stats aggregator
**Stan:** brak. Multi-sport klub nie ma jednego widoku członków.
**Co trzeba:** dashboard członka pokazujący jego aktywność cross-sport
(treningi, wyniki, statystyki) niezależnie od dyscypliny.

---

## P2 — per-klub (customizacja)

### 7. Branding & whitelabel
**Stan:** `AdminClubConfig` ma kolory + subdomena + logo. Brak: customowe CSS,
custom favicon, custom email-template header, custom SMS sender id, custom
domena (nie subdomena).
**Co trzeba:** uzupełnić w UI + walidacja + cache busting.

### 8. Konfigurowalne procesy (workflow per klub)
**Stan:** flow zapisu/płatności/komunikacji są hardcoded.
**Co trzeba:**
- Configurable onboarding flow członka (jakie pola wymagane, jakie zgody)
- Configurable email templates per zdarzenie (mamy `EmailTemplatesController`,
  rozszerzyć o triggery)
- Configurable struktury składek (różne taryfy, ulgi, kategorie wiekowe)

### 9. Feature flags per klub
**Stan:** brak.
**Co trzeba:** prosta tabela `club_features` + helper `Feature::enabled('xyz')`,
żeby pakiety cenowe (Basic/Pro/Enterprise) realnie różniły się funkcjonalnością.

---

## P3 — UX / engagement / mobile

### 10. Mobile app (Flutter)
**Stan:** `flutter_app/` to skeleton (pubspec + lib). Brak ekranów, brak API klienta.
**Co trzeba:**
- Decyzja: dokończyć Flutter czy zrezygnować na rzecz PWA?
- Jeśli Flutter: API client (z naszego REST v1), auth, member portal, push (FCM jest)
- Sklepy: Apple/Google publication (dokumenty prawne — patrz cross-cutting)

### 11. Live updates (WebSocket/SSE)
**Stan:** `LivestreamController` partial.
**Co trzeba:** SSE endpoint dla live score + push do FCM. Engagement booster
w trakcie meczu/turnieju.

### 12. Generowanie dokumentów PDF
**Stan:** `mPDF` w helpers, `ResultImageController` istnieje. Brak szablonów.
**Co trzeba:** szablony PDF: zaświadczenia członkostwa, faktury (mamy
`AdminInvoicesController` — czy generuje PDF?), umowy, certyfikaty.

---

## P4 — operacyjne

### 13. Google Calendar sync
Dwukierunkowy sync wydarzeń klubu z Kalendarzem Google trenerów/członków.

### 14. Elasticsearch (opcjonalnie)
SQL fallback działa. ES dopiero gdy >5k członków per klub i wąsko gardłowe wyszukiwanie.

### 15. InPost Paczkomaty
Dla klubów sprzedających sprzęt / merch.

---

## Cross-cutting (czeka na decyzję wyższego poziomu)

- **Multi-tenant izolacja** — czeka na wzorzec z Hovera/Billu (Todoist task #1)
- **Dokumenty prawne SaaS** — template'y wspólne dla Shootero/Billu/Hovera/ClubDesk (notatka strategiczna)
- **Tłumaczenia EN** — istnieje, ale wymaga native speaker review (Todoist task #5)

---

## Sugerowany porządek pracy (3 miesiące)

**Miesiąc 1 — revenue unblock:**
1. Przelewy24 (P0 #1) — najpopularniejszy w PL
2. Feature flags per klub (P2 #9) — fundament dla pakietów cenowych

**Miesiąc 2 — multi-sport domknięcie:**
3. Per-sport migrations grupy „sporty walki" + „raketkowe" (P1 #3)
4. Auto-rankingi MVP dla 1 sportu (P0 #2) — najpierw piłka, replikacja potem
5. TPay + PayU (P0 #1)

**Miesiąc 3 — UX i per-klub:**
6. Whitelabel pełny (P2 #7)
7. Dokumenty PDF (P3 #12)
8. Decyzja Flutter vs PWA (P3 #10) + start implementacji

W każdym miesiącu zostawić ~20% bandwidth na obsługę pierwszych klientów
z outreach (Todoist task #3).
