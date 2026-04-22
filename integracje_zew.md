# Integracje Zewnętrzne — ClubDesk

Dokument opisuje wszystkie integracje z zewnętrznymi systemami, ich status implementacji, wymagane dane uwierzytelniające oraz instrukcje konfiguracji.

---

## Tabela przeglądowa

| Integracja | Status | Gdzie po klucze | Pilność |
|---|---|---|---|
| Stripe płatności | ✅ Wdrożona — wymaga konfiguracji | stripe.com → Dashboard → API keys | Wysoka |
| SMTP e-mail | ✅ Wdrożona — wymaga konfiguracji | hosting / GSuite / SendGrid | Wysoka |
| SMSAPI.pl | ✅ Wdrożona — wymaga konfiguracji | smsapi.pl → Panel → API | Średnia |
| Twilio SMS | ✅ Wdrożona — wymaga konfiguracji | twilio.com → Console | Średnia |
| Firebase FCM push | ✅ Wdrożona — wymaga konfiguracji | console.firebase.google.com | Średnia |
| Sentry monitoring | ✅ Wdrożona — wymaga DSN | sentry.io → Project → DSN | Średnia |
| REST API v1 | ✅ Wdrożona — klucze generowane w systemie | Panel → Klucze API | Niska |
| iCal subskrypcja | ✅ Wdrożona — token w ustawieniach | Panel → Ustawienia → Kalendarz | Niska |
| Webhooki wychodzące | ✅ Wdrożona — konfiguracja per klub | Panel → Webhooks | Niska |
| QR kody | ✅ Wdrożona — bezpłatna, bez kluczy | api.qrserver.com — nie wymaga rejestracji | — |
| mPDF (PDF) | ✅ Wdrożona — biblioteka lokalna | composer (mpdf/mpdf) — bez zewnętrznych kluczy | — |
| PZSS (strzelectwo) | ⚠️ Częściowa — scraping | system.pzss.pl — konto klubowe PZSS | Niska |
| PZPN (piłka nożna) | ⚠️ Częściowa — API | api.pzpn.pl — kontakt z PZPN IT | Niska |
| Elasticsearch | ⚠️ Opcjonalna — SQL fallback działa | elastic.co lub self-hosted | Niska |
| Przelewy24 | ❌ Nie wdrożona | panel.przelewy24.pl — konto merchant | Wysoka |
| TPay | ❌ Nie wdrożona | tpay.com/dla-biznesu — umowa merchant | Wysoka |
| InPost Paczkomaty | ❌ Nie wdrożona | inpost.pl/developer — klucz API | Niska |
| Google Calendar sync | ❌ Nie wdrożona | console.cloud.google.com — OAuth 2.0 | Niska |
| Pozostałe federacje | ❌ Linki manualne | Kontakt indywidualny per federacja | Niska |

---

## Integracje wdrożone — instrukcje konfiguracji

### 1. Stripe — płatności online

**Pliki:** `app/Helpers/PaymentGateway.php`, `app/Controllers/PaymentWebhookController.php`
**Webhook URL:** `POST /webhook/payment`

**Wymagane klucze** (konfiguracja per klub w `club_settings`):

| Klucz w `club_settings` | Opis | Gdzie pobrać |
|---|---|---|
| `stripe_secret_key` | Klucz tajny Stripe (sk_live_...) | stripe.com → Developers → API keys |
| `stripe_webhook_secret` | Secret do weryfikacji webhooków (whsec_...) | stripe.com → Developers → Webhooks → endpoint |

**Kroki:**
1. Utwórz konto na stripe.com (KRS, konto bankowe, weryfikacja tożsamości)
2. Skopiuj `Secret key` z sekcji Developers → API keys
3. Utwórz webhook endpoint → `https://twojadomena.pl/webhook/payment` → zdarzenie `checkout.session.completed`
4. Skopiuj `Signing secret` (whsec_...)
5. W panelu admina → Konfiguracja klubu → wpisz oba klucze

> ⚠️ Klucze są automatycznie szyfrowane AES-256-GCM w bazie danych.

---

### 2. SMTP — wysyłka e-mail

**Pliki:** `app/Helpers/EmailService.php`, `cli/email_worker.php`
**Wzorzec:** kolejka (`email_queue`) + worker CLI (odporny na timeout)

**Wymagane ustawienia** (per klub w `club_settings`):

| Klucz | Opis |
|---|---|
| `smtp_host` | Serwer SMTP, np. `smtp.gmail.com` lub `mail.twojserwer.pl` |
| `smtp_port` | Port: 587 (STARTTLS), 465 (SSL), 25 |
| `smtp_user` | Login SMTP (adres e-mail) |
| `smtp_pass_enc` | Hasło SMTP (auto-szyfrowane) |
| `smtp_from_email` | Adres nadawcy, np. `klub@twojadomena.pl` |
| `smtp_from_name` | Nazwa nadawcy, np. `KS Przykład` |
| `smtp_encryption` | `tls` lub `ssl` |

**Polecane dostawce SMTP:**
- **GSuite / Google Workspace** — smtp.gmail.com:587 (wymaga hasła aplikacji)
- **SendGrid** — smtp.sendgrid.net:587 (klucz API jako hasło, login: `apikey`)
- **Mailtrap** (środowisko testowe) — bezpłatne
- **Hosting OVH/nazwa.pl** — dane SMTP od hostingodawcy

**Uruchomienie workera:**
```bash
php cli/email_worker.php
```
Zalecane: uruchom przez `supervisor` lub `cron` (co minutę).

---

### 3. SMSAPI.pl — SMS-y

**Plik:** `app/Helpers/SmsService.php`
**Wzorzec:** kolejka `sms_queue` + integracja SMSAPI REST API

**Wymagane klucze** (per klub w `club_settings`):

| Klucz | Opis | Gdzie |
|---|---|---|
| `sms_api_token` | Token OAuth SMSAPI (auto-szyfrowany) | smsapi.pl → Panel → API → OAuth tokens |
| `sms_sender_name` | Nazwa nadawcy (max 11 znaków) | smsapi.pl → Panel → Nadawcy (wymaga rejestracji) |
| `sms_provider` | `smsapi` (domyślnie) lub `twilio` | w kodzie |

**Kroki:**
1. Zarejestruj konto na smsapi.pl (weryfikacja NIP/PESEL)
2. Doładuj konto lub wybierz pakiet
3. Wygeneruj token OAuth: Panel → API → OAuth
4. Opcjonalnie zarejestruj nazwę nadawcy (7–14 dni roboczych)

---

### 4. Twilio — SMS / backup

**Plik:** `app/Helpers/SmsService.php`

**Wymagane klucze** (per klub w `club_settings`):

| Klucz | Opis | Gdzie |
|---|---|---|
| `twilio_sid` | Account SID | twilio.com → Console → Account Info |
| `twilio_token` | Auth Token (auto-szyfrowany) | twilio.com → Console → Account Info |
| `twilio_from` | Numer Twilio (+48...) | twilio.com → Phone Numbers |

**Uwaga:** `sms_provider = twilio` w `club_settings` przełącza na Twilio zamiast SMSAPI.

---

### 5. Firebase FCM — powiadomienia push

**Plik:** `app/Helpers/PushService.php`
**Konfiguracja:** globalna (dla całej platformy) w tabeli `settings`

**Wymagane klucze** (tabela `settings`, klucz globalny):

| Klucz w `settings` | Opis | Gdzie |
|---|---|---|
| `fcm_project_id` | ID projektu Firebase | console.firebase.google.com → Project Settings → General |
| `fcm_server_key` | Server key (auto-szyfrowany) | console.firebase.google.com → Project Settings → Cloud Messaging |

**Kroki:**
1. Utwórz projekt na console.firebase.google.com
2. Dodaj aplikację (Android/iOS/Web)
3. Skopiuj Project ID i Server Key (Legacy) lub utwórz Service Account (FCM v1)
4. Wpisz w panelu admina → Ustawienia platformy

---

### 6. Sentry — monitoring błędów

**Plik:** `app/Helpers/ErrorMonitor.php`, `config/app.php`

**Wymagane klucze:**

| Plik | Klucz | Gdzie |
|---|---|---|
| `config/app.php` | `sentry_dsn` | sentry.io → Project → Settings → DSN |

**Kroki:**
1. Utwórz konto na sentry.io (bezpłatny plan wystarczy na start)
2. Utwórz projekt PHP
3. Skopiuj DSN (format: `https://xxx@xxx.ingest.sentry.io/xxx`)
4. Wpisz w `config/app.php` lub `config/app.local.php`:
```php
'sentry_dsn' => 'https://xxx@xxx.ingest.sentry.io/xxx',
```

---

### 7. REST API v1 — integracja zewnętrzna / mobilna

**Pliki:** `app/Controllers/Api/` (7 kontrolerów)
**Uwierzytelnianie:** Bearer token (`Authorization: Bearer ks_...`)
**Dokumentacja tras:**

| Metoda | Endpoint | Opis | Scope |
|---|---|---|---|
| POST | `/api/v1/auth/login` | Logowanie (zwraca token) | — |
| GET | `/api/v1/members` | Lista zawodników | `members:read` |
| GET | `/api/v1/members/:id` | Szczegóły zawodnika | `members:read` |
| GET | `/api/v1/events` | Lista wydarzeń | `events:read` |
| GET | `/api/v1/events/upcoming` | Nadchodzące wydarzenia | `events:read` |
| GET | `/api/v1/payments` | Lista wpłat | `payments:read` |
| GET | `/api/v1/payments/summary` | Podsumowanie finansowe | `payments:read` |
| GET | `/api/v1/sports` | Aktywne sekcje sportowe | `sports:read` |
| GET | `/api/v1/sports/catalog` | Katalog wszystkich sportów | `sports:read` |
| POST | `/api/v1/devices/register` | Rejestracja urządzenia push | `devices:write` |
| POST | `/api/v1/devices/unregister` | Wyrejestrowanie urządzenia | `devices:write` |

**Generowanie klucza:**
Panel → Klucze API → Wygeneruj nowy klucz  
Klucz wyświetlany tylko raz — przechowywany jako bcrypt hash.

**Rate limiting:** domyślnie 60 żądań/minutę per klucz.

---

### 8. iCal — subskrypcja kalendarza

**Plik:** `app/Helpers/IcsGenerator.php`, `app/Controllers/CalendarController.php`
**Endpoint publiczny:** `GET /cal/{token}`

**Jak działa:**
- Każdy klub ma unikalny token iCal w `club_settings` (klucz: `ical_token`)
- Token generowany automatycznie przy pierwszym wejściu na stronę subskrypcji
- URL do subskrypcji: `https://twojadomena.pl/cal/{token}`
- Zwraca `text/calendar` — kompatybilny z Google Calendar, Apple Calendar, Outlook

**Generowanie tokenu:**
Panel → Kalendarz → "Subskrybuj w zewnętrznym kalendarzu" → skopiuj URL

**Zawartość feeda:** wszystkie publiczne zdarzenia kalendarza klubu (następne 365 dni).

---

### 9. Webhooki wychodzące

**Plik:** `app/Helpers/WebhookService.php`
**Konfiguracja:** per klub, tabela `webhook_endpoints`

**Zdarzenia:** `member.created`, `payment.received`, `event.created`, itp.
**Bezpieczeństwo:** HMAC-SHA256 podpis w nagłówku `X-KlubSportowy-Signature`

**Konfiguracja:** Panel → Webhooks → Dodaj endpoint → podaj URL + zdarzenia

Nie wymaga zewnętrznych kluczy API — odbiorca konfiguruje swój endpoint.

---

## Integracje częściowe — wymagają kluczy

### 10. Federacje sportowe

**Plik:** `app/Helpers/FederationClient.php`
**Tabela:** `federations` (12 wpisów predefiniowanych)

#### PZSS — Polski Związek Strzelectwa Sportowego

**Metoda:** scraping (brak oficjalnego API)

| Klucz | Opis | Gdzie |
|---|---|---|
| `federation_pzss_login` | Login konta klubowego w PZSS | system.pzss.pl — rejestracja klubu |
| `federation_pzss_pass` | Hasło (auto-szyfrowane) | system.pzss.pl |

> ⚠️ Moduł shooting jest obsługiwany przez shotero.pl dla zaawansowanych funkcji PZSS.

#### PZPN — Polski Związek Piłki Nożnej

**Metoda:** API REST (wymaga autoryzacji)

| Klucz | Opis | Gdzie |
|---|---|---|
| `federation_pzpn_api_key` | Klucz API (auto-szyfrowany) | api.pzpn.pl — kontakt: it@pzpn.pl |

**Uwaga:** PZPN udostępnia API partnerom — wymaga podpisania umowy.

#### Pozostałe federacje (20 federacji — obsługiwane przez ręczne linki)

Aktualnie obsługiwane przez **ręczne linki** do portali federacyjnych (bez API). Dla każdej z nich można w przyszłości podpiąć klucz API poprzez `club_settings` klucz: `federation_{skrót}_api_key`.

| Federacja | Sport | Portal | Kontakt |
|---|---|---|---|
| PZKosz | Koszykówka | pzkosz.pl | sekretariat@pzkosz.pl |
| PZPS | Siatkówka | pzps.pl | biuro@pzps.pl |
| PZLA | Lekkoatletyka | pzla.pl | biuro@pzla.pl |
| PZRugby | Rugby | pzrugby.pl | biuro@pzrugby.pl |
| PZJudo | Judo | pzjudo.pl | biuro@pzjudo.pl |
| PZKarate | Karate | pzkarate.pl | kontakt@pzkarate.pl |
| **PZP** | **Pływanie** | polswim.pl | biuro@polswim.pl |
| **PZT** | **Tenis ziemny** | polskitenis.pl | biuro@polskitenis.pl |
| **PZBoks** | **Boks** | pzb.pl | biuro@pzb.pl |
| **ZPRP** | **Piłka ręczna** | zprp.pl | kontakt@zprp.pl |
| **PZKol** | **Kolarstwo** | pzkol.pl | biuro@pzkol.pl |
| **PZHL** | **Hokej na lodzie** | pzhl.org.pl | sekretariat@pzhl.org.pl |
| **PZSzerm** | **Szermierka** | pzszerm.pl | biuro@pzszerm.pl |
| **PZTkd** | **Taekwondo WTF** | pztkd.pl | biuro@pztkd.pl |
| **PKC (PZPC)** | **Podnoszenie ciężarów** | pzpc.pl | biuro@pzpc.pl |
| **PZA** | **Wspinaczka sportowa** | pza.org.pl | biuro@pza.org.pl |

> **Pogrubione** = nowo dodane sporty w batchach N1-N10.

**Konfiguracja per klub (w panelu admin → Konfiguracja klubu → Sekcje sportowe):**
- `sport_{key}_federation_id` — ID klubu w federacji (np. kod licencji zespołowej)
- `sport_{key}_federation_login` / `_pass` — jeśli federacja ma portal z logowaniem
- `sport_{key}_federation_api_key` — jeśli federacja udostępnia REST API

---

### 11. Elasticsearch (opcjonalny)

**Plik:** `app/Helpers/SearchEngine.php`
**Fallback:** pełnofunkcjonalne wyszukiwanie SQL działa bez Elasticsearch.

**Konfiguracja:** zmienna środowiskowa `ELASTICSEARCH_URL` (`.env` lub `config/app.local.php`):
```php
'elasticsearch_url' => 'http://localhost:9200',
```

**Opcje:**
- **Self-hosted:** `docker run -p 9200:9200 elasticsearch:8.x`
- **Elastic Cloud:** elastic.co → Create deployment → skopiuj Cloud ID + API key

---

## Integracje do wdrożenia — wymagają umów/kont

### 12. Przelewy24 — płatności polskie

**Status:** nie wdrożona (kod stub)  
**Priorytet:** Wysoki — preferowana bramka dla polskich użytkowników

**Wymagane:**
- Konto merchant na panel.przelewy24.pl
- Weryfikacja firmy (KRS/CEIDG, właściciel konta bankowego)

**Klucze do skonfigurowania po otwarciu konta:**

| Klucz | Opis |
|---|---|
| `p24_merchant_id` | Merchant ID z panelu Przelewy24 |
| `p24_pos_id` | POS ID (punkt sprzedaży) |
| `p24_crc_key` | CRC key (auto-szyfrowany) |
| `p24_api_key` | API key (auto-szyfrowany) |
| `p24_sandbox` | `1` dla środowiska testowego |

**Kontakt:** kontakt@przelewy24.pl lub panel.przelewy24.pl → Rejestracja

---

### 13. TPay — alternatywa dla Przelewy24

**Status:** nie wdrożona  
**Wymagane:** umowa merchant

**Kontakt:** tpay.com/dla-biznesu → formularz rejestracyjny

---

### 14. InPost Paczkomaty

**Status:** nie wdrożona (potencjalnie dla modułu sklepu)  
**Wymagane:** klucz API InPost

**Klucze:**

| Klucz | Opis |
|---|---|
| `inpost_api_key` | Klucz API (panel manager.inpost.pl) |
| `inpost_organization_id` | ID organizacji |

**Kontakt:** inpost.pl/developer → Rejestracja konta API

---

### 15. Google Calendar — synchronizacja dwukierunkowa

**Status:** nie wdrożona (dostępna subskrypcja iCal → Google Calendar jako alternatywa)

**Wymagane:**
- Projekt w Google Cloud Console
- OAuth 2.0 credentials (Client ID + Secret)
- Calendar API włączone

**Alternatywa bez kluczy:** użyj iCal subscription URL (`/cal/{token}`) — Google Calendar importuje automatycznie co ~24h.

**Kontakt/rejestracja:** console.cloud.google.com → Create Project → Enable Calendar API

---

## Konfiguracja pliku `.env`

Utwórz plik `config/app.local.php` (nadpisuje `config/app.php`, ignorowany przez git):

```php
<?php
return [
    // Monitoring
    'sentry_dsn'          => 'https://xxx@xxx.ingest.sentry.io/xxx',

    // Elasticsearch (opcjonalny)
    'elasticsearch_url'   => 'http://localhost:9200',

    // Klucze globalne (FCM — jeśli nie konfigurowane per klub)
    // 'fcm_project_id'   => 'moj-projekt-firebase',
    // 'fcm_server_key'   => 'AAAAxxx...',
];
```

Klucze per klub (Stripe, SMTP, SMS, Twilio, FCM, federacje) konfiguruj przez:
**Panel admina → Zarządzanie klubem → Ustawienia integracji**

---

## Bezpieczeństwo kluczy

Wszystkie wrażliwe klucze w `club_settings` są automatycznie szyfrowane **AES-256-GCM** przed zapisem do bazy danych. Dotyczy kluczy zawierających: `_pass`, `_secret`, `_api_key` w nazwie.

Implementacja: `app/Models/ClubSettingsModel.php` + `app/Helpers/Encryption.php`

Klucz szyfrowania ustawiany w zmiennej środowiskowej `APP_KEY` lub `config/app.php`:
```php
'encryption_key' => 'base64:xxx...', // 32 bajty, base64
```

---

## Odporność na błędy (anti-timeout)

| Mechanizm | Dotyczy | Implementacja |
|---|---|---|
| Kolejka e-mail | SMTP | `email_queue` + `cli/email_worker.php` (async) |
| Kolejka SMS | SMSAPI / Twilio | `sms_queue` + worker CLI (async) |
| cURL timeout 10s | Federacje, FCM, webhooki | `CURLOPT_TIMEOUT => 10` w FederationClient |
| Try/catch API | REST API, webhooki | BaseApiController, WebhookService |
| SQL fallback | Elasticsearch | SearchEngine::search() → SQL gdy ES niedostępny |
| Rate limiting | REST API | RateLimiter per klucz (tokeny na minutę) |
| DB error log | Sentry | ErrorMonitor → DB + Sentry (try/catch) |

---

## Wymagania regulacyjne vs. tabele (profile M1–M6)

Po wdrożeniu batchy M1–M6 system spełnia następujące wymagania prawne:

| Tabela | Regulacja / wymóg | Dotyczy sportów |
|---|---|---|
| `body_metrics` | IWF/UCI: waga potrzebna do Sinclair / W/kg; boks: kategoria wagowa | weightlifting, cycling, boxing, BJJ, wrestling, taekwondo |
| `member_emergency_contacts` | RODO art. 9 + ustawa o sporcie (bezpieczeństwo) | wszystkie sporty kontaktowe |
| `athlete_training_logs` | Wewnętrzne, opcjonalne (audyt obciążeń treningowych) | cycling, swimming, weightlifting, triathlon, climbing |
| `anti_doping_declarations` | **Kodeks WADA 2025** + polska ustawa antydopingowa | weightlifting (IWF), boxing (PZBoks), swimming (FINA), taekwondo (WTF), cycling (UCI), wrestling, judo, athletics, gymnastics, climbing, sambo |
| `minor_consents` | KRC art. 101 + RODO art. 8 (wiek 13+/16+) | wszystkie, gdzie są zawodnicy < 18 lat |
| `club_equipment_items/_assignments` | Ustawa o rachunkowości art. 26 (inwentarz) + odpowiedzialność materialna | wszystkie dla sprzętu klubowego |
| `coach_certifications` | **Ustawa o sporcie art. 41** — trener/instruktor musi mieć uprawnienia | wszystkie sekcje klubowe |

---

## Nowe integracje rekomendowane (do wdrożenia w przyszłości)

### A. WADA ADAMS (Anti-Doping Administration System)

**Status:** nie wdrożona — rekomendacja dla klubów z zawodnikami WADA-covered

**Wymagane klucze** (do uzyskania przez klub):

| Klucz w `club_settings` | Opis | Gdzie |
|---|---|---|
| `wada_adams_username` | Login do ADAMS | https://adams.wada-ama.org (wymaga konta klubu/federacji) |
| `wada_adams_password_enc` | Hasło (auto-szyfr.) | j.w. |

**Uwaga:** ADAMS API nie jest publiczne — dostęp wymaga umowy z POLADA.
**Kontakt:** kontakt@polada.pl — potwierdzenie statusu klubu i zawodników TUE.

### B. Wearable sync (Garmin, Strava, Polar, Apple HealthKit)

**Status:** nie wdrożona — rekomendacja dla cyclingu, swimmingu, triathlonu

**Mechanizm:** OAuth 2.0 per zawodnik — zawodnik autoryzuje import swoich treningów do `athlete_training_logs` (batch M3)

**Wymagane klucze globalne** (w `settings`):

| Platforma | Klucze | Gdzie |
|---|---|---|
| Strava | `strava_client_id`, `strava_client_secret_enc` | https://developers.strava.com |
| Garmin Connect | `garmin_consumer_key`, `garmin_consumer_secret_enc` | https://developerportal.garmin.com |
| Polar AccessLink | `polar_client_id`, `polar_client_secret_enc` | https://www.polar.com/accesslink-api |
| Apple HealthKit | — | wymaga aplikacji iOS (natywna) |

**Polecana kolejność:** Strava (darmowe, najszersze API) → Garmin → Polar.

### C. Płatności offline (SumUp, Paynow)

**Status:** nie wdrożona — uzupełnienie Stripe dla wpłat fizycznych

**Wymagane klucze:**

| Klucz | Opis | Gdzie |
|---|---|---|
| `sumup_api_key_enc` | Klucz REST API + czytnik kart Bluetooth | https://me.sumup.com/developers |
| `paynow_api_key_enc`, `paynow_signature_enc` | Autopay/mBank Paynow | https://www.paynow.pl/ |

**Use case:** zbiórka składek na imprezach klubowych, płatność za wynajem sprzętu przy wydaniu.

### D. Analytics (GA4 + Matomo)

**Status:** nie wdrożona — dla stron publicznych klubów

**Konfiguracja (per klub w `club_settings`):**
- `analytics_ga4_measurement_id` — np. `G-XXXXXXX` (GA4)
- `analytics_matomo_url` + `analytics_matomo_site_id` — self-hosted

**Bez nowych kluczy API** — snippet JS dołączany do stron publicznych klubu.

### E. Ubezpieczenia klubowe (ręczne)

**Status:** w planie M5 dodano sprzęt, ale polisy OC/NW pozostają ręczne.
**Rekomendacja:** dodać w `club_settings`:
- `insurance_oc_policy_number`, `insurance_oc_insurer`, `insurance_oc_valid_until`
- `insurance_nnw_policy_number`, `insurance_nnw_insurer`, `insurance_nnw_valid_until`

Brokerzy (Ergo Hestia, Warta, PZU): **brak publicznego API** — kontakt biznesowy.

---

## Podsumowanie — co klub ma po batchach M1-M6

**Profil zawodnika (portal + admin):**
- ✅ Pełne dane osobowe + zdjęcie + QR karta członkowska
- ✅ Badania lekarskie generyczne + sport-specific (boxing_medicals itd.)
- ✅ Licencje federacyjne z datami ważności i QR
- ✅ Zgody RODO (5 typów)
- ✅ **Pomiary ciała z historią** — waga/wzrost/BMI/BF%/HR/wingspan (M1)
- ✅ **Kontakty w razie wypadku** — multiple, primary flag (M2)
- ✅ **Dziennik treningowy self-log** — tygodniowa siatka + statystyki (M3)
- ✅ **Deklaracje anti-dopingowe** — 7 typów WADA/POLADA/IWF/UCI/FINA/WTF/narodowa (M4)
- ✅ **Zgody opiekunów** dla małoletnich — 4 kategorie + upsert (M4)
- ✅ Pasy/stopnie (taekwondo, BJJ, judo, karate, aikido)
- ✅ Powiadomienia, turnieje, plan treningów, obecność

**Profil klubu (admin):**
- ✅ Dane formalne + NIP/REGON/KRS + branding
- ✅ Sekcje sportowe z federacjami
- ✅ Facilities (boisko/sala/hala/basen/kort) + rezerwacje
- ✅ Sport-specific: weapons (shooting), sailing_boats, tennis/padel_courts
- ✅ Uprawnienia federacyjne: judge_licenses, member_licenses
- ✅ **Ujednolicony inventory sprzętu klubowego** — item + assignment tracking (M5)
- ✅ **Uprawnienia trenerskie per sport** — 12 poziomów z alertami wygasania (M6)
- ✅ **Dashboard zgodności WADA + zgód opiekunów** (M4)
- ✅ Klucze API, szyfrowanie sensytywnych pól
- ✅ Pełne szablony RODO + customizacja stylu

---

## Bezpieczeństwo (batche S1-S6)

System przetwarza dane szczególnej kategorii (RODO art. 9): medyczne, anti-doping,
pomiary ciała, kontakty awaryjne, dane opiekunów. Wdrożone zabezpieczenia:

| Warstwa | Mechanizm | Pliki |
|---|---|---|
| **Szyfrowanie DB** | AES-256-GCM na 6 tabelach medycznych/wrażliwych (trait `EncryptsFields`) | `app/Models/Traits/EncryptsFields.php` |
| **RBAC** | Dostęp do danych wrażliwych tylko dla `zarzad/trener/instruktor/lekarz` | `Auth::requireSensitiveAccess()` |
| **2FA zawodników** | TOTP (RFC 6238) + backup codes (bcrypt) | `MemberTwoFactorController` + `member_totp_backup_codes` |
| **Audit log (RODO art. 30)** | Każdy odczyt medycznych danych logowany | `SensitiveAccessLogModel`, `/admin/sensitive-access` (tylko zarząd) |
| **Izolacja klubów** | `ClubScopedModel` auto-filtr + audyt 50+ tabel | `/admin/audit/isolation` |
| **Rate limit** | Logowanie — `RateLimiter` per IP | `app/Helpers/RateLimiter.php` |
| **Security events** | Logi login/CSRF/impersonation | `security_events` + `/admin/security` |
| **Conditional UI** | Tylko aktywne sporty per klub pokazywane | `SportModel::activeKeysForClub()` |

Szczegółowa mapa bezpieczeństwa: patrz **[BEZPIECZENSTWO.md](./BEZPIECZENSTWO.md)**

### Status per sport: strzelectwo → shotero.pl

Moduł shooting w ClubDesk ma ograniczony zakres (rejestr broni, amunicja, licencje
sędziowskie). **Pełna obsługa PZSS** (integracja system.pzss.pl, patenty, import wyników,
rejestr zawodników) dostępna na dedykowanej platformie **[shotero.pl](https://shotero.pl)**.
Kluby PZSS są rekomendowane do shotero.pl — nowe funkcjonalności PZSS NIE będą dodawane
w ClubDesk. Moduł zachowany dla backward compat.

---

## Dodatkowe federacje (batche X1-X13) — droga do 50 sportów

13 kolejnych federacji z polskich związków sportowych. **Brak publicznego API** dla klubów
w większości przypadków — konfiguracja ręczna przez `club_settings[sport_{key}_federation_id]`
i ewentualnie `_login/_pass` dla portali webowych.

| # | Sport | Federacja | Portal | Kontakt | Potencjalne API |
|---|---|---|---|---|---|
| X1 | Rugby | PZRugby | pzrugby.com.pl | biuro@pzrugby.com.pl | — |
| X2 | Narciarstwo alpejskie | PZN Alpine | pzn.pl | sekretariat@pzn.pl | **FIS data.fis-ski.com (public read-only)** |
| X3 | Narciarstwo biegowe | PZN XC | pzn.pl | sekretariat@pzn.pl | **FIS data.fis-ski.com** |
| X4 | Skoki narciarskie | PZN SJ | pzn.pl | sekretariat@pzn.pl | **FIS data.fis-ski.com** |
| X5 | Snowboard | PZN SB | pzn.pl | sekretariat@pzn.pl | **FIS data.fis-ski.com** |
| X6 | Łyżwiarstwo figurowe | PZLF | pzlf.pl | biuro@pzlf.pl | ISU data (read-only) |
| X7 | Biathlon | PZBiathlon | pzbiathlon.pl | biuro@pzbiathlon.pl | IBU data.ibu.blue |
| X8 | Kickboxing | PZKick | pzkickboxing.pl | kontakt@pzkickboxing.pl | — |
| X9 | MMA | PZMMA | pzmma.pl | kontakt@pzmma.pl | — |
| X10 | Kajakarstwo | PZKajak | pzkaj.pl | pzk@pzkaj.pl | — |
| X11 | Golf | PZGolfa | pzgolf.pl | biuro@pzgolf.pl | **WHS handicap portal (USGA/R&A)** |
| X12 | Brydż sportowy | PZBS | pzbs.pl | pzbs@pzbs.pl | — |
| X13 | Hokej na trawie | PZHnT | pzhnt.pl | sekretariat@pzhnt.pl | — |

### Rekomendowane integracje (po uzyskaniu kluczy)

**FIS API (data.fis-ski.com)** — publiczny read-only API z punktami FIS dla:
- narciarstwa alpejskiego (alpine_ski_results.fis_points)
- narciarstwa biegowego (xc_ski_results.fis_points)
- skoków narciarskich (ski_jump_results.fis_points)
- snowboardu (snowboard_results.fis_points)

Wdrożenie: helper `app/Helpers/FisClient.php` → auto-sync punktów dla zawodników z `fis_id`.

**WHS Handicap** — globalny standard golfowy. Kluby z PZGA mogą synchronizować
`golf_handicaps.whs_index` z centralnego rejestru USGA/R&A przez oficjalne API
(wymaga kontaktu z PZGA dla dostępu).

### Status wszystkich 50 sportów

ClubDesk obsługuje teraz **50 sportów** z polskich związków sportowych:

1-37: (wcześniejsze batche) — football, basketball, volleyball, shooting, athletics,
karate, aikido, wrestling, sambo, chess, crossfit, powerlifting, rowing, badminton,
archery, gymnastics, padel, table_tennis, squash, floorball, judo, bjj, sailing,
triathlon, swimming, tennis, boxing, handball, cycling, icehockey, fencing, taekwondo,
weightlifting, climbing, dancesport, equestrian, rollerskating

38-50: (batche X1-X13) — rugby, alpineski, xcski, skijump, snowboard, figureskating,
biathlon, kickboxing, mma, kayaking, golf, bridge, fieldhockey
