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

#### Pozostałe federacje (10 federacji)

Aktualnie obsługiwane przez **ręczne linki** do portali federacyjnych (bez API):

| Federacja | Portal | Kontakt |
|---|---|---|
| PZKosz | pzkosz.pl | sekretariat@pzkosz.pl |
| PZPS (siatkówka) | pzps.pl | biuro@pzps.pl |
| PZLA (lekkoatletyka) | pzla.pl | biuro@pzla.pl |
| PZHokeja | pzhl.pl | biuro@pzhl.pl |
| PZRugby | pzrugby.pl | biuro@pzrugby.pl |
| PZTenisa | pzt.pl | biuro@pzt.pl |
| PZPływacki | pzp.com.pl | biuro@pzp.com.pl |
| PZWioślarstwa | rowing.org.pl | biuro@rowing.org.pl |
| PZJudo | pzjudo.pl | biuro@pzjudo.pl |
| PZKarate | pzkarate.pl | kontakt@pzkarate.pl |

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
