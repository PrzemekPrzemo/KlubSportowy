# Bezpieczeństwo — ClubDesk

Dokument opisuje zabezpieczenia systemu po wdrożeniu batchy S1-S6. Adresat: zarząd klubu, administrator platformy, inspektor RODO.

## Podstawa prawna

- **RODO art. 9** — dane szczególnej kategorii (zdrowie, PESEL)
- **RODO art. 30** — rejestr czynności przetwarzania
- **RODO art. 32** — środki techniczne (szyfrowanie, kontrola dostępu)
- **Ustawa o sporcie art. 41** — kwalifikacje trenera/instruktora
- **Kodeks WADA 2025** — deklaracje anti-dopingowe

---

## 1. Szyfrowanie danych w spoczynku (at-rest)

### Algorytm
AES-256-GCM (authenticated encryption) + SHA-256 hash do wyszukiwania indeksowanego.

### Szyfrowane kolumny

| Tabela | Kolumny szyfrowane |
|---|---|
| `members` | `pesel`, `email`, `phone` (+ hashe SHA-256) |
| `club_settings` | dowolna wartość gdy `key` zawiera `_pass`, `_secret`, `_api_key` |
| `member_medical_exams` | `doctor_name`, `notes`, `document_path` |
| `boxing_medicals` | `doctor_name`, `notes` |
| `anti_doping_declarations` | `witness`, `notes`, `document_path` |
| `member_emergency_contacts` | `phone`, `phone_alt`, `email`, `notes` |
| `body_metrics` | `notes`, `measured_by` |
| `minor_consents` | `guardian_phone`, `guardian_email`, `guardian_id_number`, `notes`, `document_path` |

### Implementacja
- `app/Helpers/Encryption.php` — core AES-256-GCM
- `app/Models/Traits/EncryptsFields.php` — reusable trait dla modeli
- Klucz w `config/encryption.local.php` lub zmienna `APP_ENCRYPTION_KEY`

### Migracja istniejących danych
Nowe wartości są automatycznie szyfrowane. Legacy plaintext działa (fallback).
Do masowej migracji: `php cli/migrate_encrypt_existing.php` (do dodania w razie potrzeby).

---

## 2. Role-Based Access Control (RBAC)

### Role w systemie

| Rola | Opis | Dostęp do danych wrażliwych |
|---|---|:---:|
| `zarzad` | Zarząd klubu (pełne uprawnienia) | ✅ |
| `trener` | Trener sekcji | ✅ |
| `instruktor` | Instruktor | ✅ |
| `lekarz` | Lekarz klubowy | ✅ |
| `sedzia` | Sędzia | ❌ |
| `ksiegowy` | Księgowy (tylko finanse) | ❌ |

### Dane wrażliwe (dostęp ograniczony)

Dostępne tylko dla ról `SENSITIVE_ROLES` + super admin:
- Badania lekarskie (`/medical`, `/members/:id/medical`)
- Pomiary ciała (`/members/:id/metrics`)
- Kontakty awaryjne (`/members/:id/emergency-contacts`)
- Anti-doping + zgody opiekunów (`/admin/compliance`)
- Badania bokserskie (`/boxing/medicals`)

### Implementacja
- `app/Helpers/Auth.php`:
  - `SENSITIVE_ROLES` = ['zarzad', 'trener', 'instruktor', 'lekarz']
  - `canAccessSensitiveData(): bool`
  - `requireSensitiveAccess(): void` — 403 + flash + redirect
- `app/Controllers/BaseController.php::requireSensitiveAccess()`
- Użycie w konstruktorach: BodyMetricsController, EmergencyContactsController, ComplianceController, MedicalExamsController, Boxing/MedicalsController

### Portal zawodnika
Zawodnik widzi TYLKO SWOJE dane (po `member_id` z sesji). Brak dostępu do cudzych.

---

## 3. Uwierzytelnianie dwuetapowe (2FA)

### Dla adminów (`users`)
- TOTP (RFC 6238) via `Totp` helper
- Kolumny: `users.totp_enabled`, `totp_secret`, `totp_confirmed_at`
- Backup codes: `user_totp_backup_codes` (bcrypt)
- Konfiguracja: `/account/2fa/setup`

### Dla zawodników (`members`) — S3
- TOTP (Google Authenticator / Authy / Microsoft Authenticator)
- Kolumny: `members.totp_enabled`, `totp_secret`, `totp_confirmed_at`
- Backup codes: `member_totp_backup_codes`
- Konfiguracja: `/portal/2fa/setup`
- Weryfikacja po logowaniu: `/portal/2fa/verify`

### Flow logowania
1. Użytkownik wpisuje email+hasło
2. Jeśli `totp_enabled=1` → `pending_member_id` w sesji + redirect `/portal/2fa/verify`
3. Wpisuje 6-cyfrowy kod (lub 8-znakowy backup)
4. Po weryfikacji pełny `MemberAuth::login()`

---

## 4. Audit log (RODO art. 30)

### Tabela `sensitive_access_log`
Rejestruje każdy odczyt danych wrażliwych:
- `user_id`, `member_id` (którego dane), `data_type`, `action`, `ip`, `user_agent`, `created_at`

### Kontrolery z logowaniem
- `BodyMetricsController::member()` → `log('body_metrics', 'view')`
- `EmergencyContactsController::member()` → `log('emergency_contacts', 'view')`
- `ComplianceController::index()` → `log('anti_doping', 'list')`
- `MedicalExamsController::index()` → `log('medical', 'list')`

### Dostęp do logu
`/admin/sensitive-access` — **tylko rola `zarzad`** (nawet trener/instruktor/lekarz nie widzi audytu).

---

## 5. Izolacja danych per klub

### Mechanizm
Wszystkie tabele domenowe mają kolumnę `club_id` (FK → clubs).
Modele dziedziczą `ClubScopedModel` — automatyczny filtr `WHERE club_id = ?` z `ClubContext`.

### Audyt
- `AdminAuditController::isolation()` — `/admin/audit/isolation`
- Sprawdza `CLUB_SCOPED_TABLES` (lista 50+ tabel) pod kątem:
  - Osieroconych `club_id` (nie istnieje w `clubs`)
  - Rekordów bez `club_id` (NULL)
- Raportowanie: tabela per-check z przykładami affected records

### Super admin
`Auth::isSuperAdmin()` omija filtr przez `ClubScopedModel::withoutScope()`.

---

## 6. Conditional UI per active sports

Funkcjonalność jest dostępna tylko dla sportów **aktywnych w klubie** (`club_sports.is_active=1`):

- Nav `quick-switch` filtruje per `cs_active` (zarząd widzi wszystkie z badge)
- Dashboard portalu: kafelek "Pasy" tylko gdy klub ma aktywny bjj/judo/karate/aikido/taekwondo
- Trait `RequiresActiveSport` dla kontrolerów sportowych

---

## 7. Strzelectwo → shotero.pl

Sekcja strzelecka w ClubDesk ma ograniczony zakres. **Pełna obsługa PZSS** (licencje, patenty, rejestry sędziów, integracja z system.pzss.pl, import wyników) dostępna na dedykowanej platformie **[shotero.pl](https://shotero.pl)**.

- Info banner na każdym widoku shooting (`_shotero_banner.php`)
- Badge "shotero.pl" w nav quick-switch
- AdminPlatformController blokuje globalne wyłączenie modułu (backward compat)

---

## 8. Rate limiting + Security events

- `app/Helpers/RateLimiter.php` — limit prób logowania per IP
- `security_events` tabela — logi `login_success/failed/logout/csrf_violation/rate_limit_hit`
- Widok admin: `/admin/security` (SecurityEventModel)

---

## 9. Ochrona sesji

- Regenerate session ID po logowaniu (prevent fixation)
- Cookie: `HttpOnly`, `SameSite=Lax`, `Secure` (w produkcji)
- CSRF token w każdym formularzu (`Csrf::verify()` przed zapisem)

---

## 10. Weryfikacja end-to-end

1. **Szyfrowanie DB:** `SELECT notes FROM member_medical_exams LIMIT 1;` → zaszyfrowany base64
2. **RBAC:** zaloguj jako `ksiegowy` → `/medical` → 403 + flash
3. **2FA:** zawodnik `/portal/2fa/setup` → QR → kod → logowanie wymaga kodu
4. **Audit:** admin (zarzad) otwiera `/members/1/metrics` → wpis w `sensitive_access_log`
5. **Conditional UI:** klub bez swimming → brak linku "Pływanie" w quick-switch
6. **Shooting:** `/shooting/weapons` → info banner shotero.pl u góry
7. **Izolacja:** `/admin/audit/isolation` → wszystkie checki zielone
