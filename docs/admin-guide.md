# ClubDesk — Przewodnik super administratora

Dokument przeznaczony dla administratorów platformy (rola `super_admin`).

---

## 1. Zarządzanie klubami

### Lista klubów
- **Panel SA → Kluby** — tabela wszystkich zarejestrowanych klubów z informacjami: nazwa, plan, status subskrypcji, data rejestracji, liczba zawodników.
- Filtrowanie po statusie: aktywny, zawieszony, demo, wygasły.

### Tworzenie klubu
1. Kliknij **Dodaj klub**.
2. Wypełnij dane: nazwa, e-mail właściciela, plan subskrypcyjny.
3. System wyśle e-mail z linkiem aktywacyjnym do właściciela.

### Edycja i zawieszenie
- Edytuj dane klubu, zmień plan, przedłuż subskrypcję.
- **Zawieś klub** — blokuje logowanie użytkowników klubu; dane pozostają w bazie.
- **Usuń klub** — nieodwracalne usunięcie wszystkich danych klubu. Wymaga podwójnego potwierdzenia.

---

## 2. Katalog sportów

- **Panel SA → Sporty** — globalna lista dyscyplin sportowych dostępnych w systemie.
- Każdy sport posiada: nazwę, ikonę (klasa CSS / SVG), klucz systemowy (`sport_key`), flagę aktywności.
- Dodawanie nowej dyscypliny: podaj nazwę, klucz, ikonę. Po zapisie dyscyplina staje się dostępna dla wszystkich klubów.
- Dezaktywacja sportu ukrywa go z listy wyboru przy tworzeniu nowej sekcji, ale nie wpływa na istniejące sekcje.

---

## 3. Plany subskrypcyjne

### Definiowanie planów
- **Panel SA → Plany** — lista planów (np. Free, Basic, Pro, Enterprise).
- Każdy plan określa: nazwę, cenę, okres (miesiąc/rok), limity (liczba zawodników, liczba sekcji, pojemność załączników).

### Przypisywanie
- Plan przypisywany jest do klubu przy tworzeniu lub edycji.
- Zmiana planu obowiązuje od następnego okresu rozliczeniowego.

### Limity
- Gdy klub osiągnie limit planu (np. maks. zawodników), system blokuje dodawanie nowych z komunikatem o konieczności rozszerzenia planu.

---

## 4. Demo

- Każdy nowy klub może korzystać z 14-dniowego okresu demo (konfigurowalne w **Panel SA → Ustawienia → Demo**).
- W trybie demo dostępne są wszystkie moduły bez ograniczeń.
- Po wygaśnięciu demo klub jest proszony o wybór planu płatnego; dane nie są usuwane.
- Super administrator może ręcznie przedłużyć okres demo dla wybranego klubu.

---

## 5. Reklamy

- **Panel SA → Reklamy** — zarządzanie banerami reklamowymi wyświetlanymi w panelu klubowym (wyłącznie w planie Free).
- Dodaj reklamę: tytuł, grafika (JPG/PNG, max 500 KB), link docelowy, daty wyświetlania.
- Pozycje: `sidebar`, `top_banner`, `footer`.
- Statystyki: wyświetlenia i kliknięcia dostępne w tabeli reklam.
- Kluby z planem płatnym nie widzą reklam.

---

## 6. Log aktywności

- **Panel SA → Logi** — centralny rejestr zdarzeń systemowych.
- Rejestrowane akcje: logowanie, zmiana uprawnień, tworzenie/usuwanie klubu, eksport danych, tworzenie kopii zapasowych.
- Filtry: zakres dat, typ akcji, użytkownik, klub.
- Logi przechowywane przez 365 dni, potem automatycznie archiwizowane.
- Eksport logów do CSV.

---

## 7. Impersonation

Funkcja pozwala super administratorowi zalogować się jako dowolny użytkownik klubu bez znajomości jego hasła.

1. Przejdź do **Panel SA → Kluby → [klub] → Użytkownicy**.
2. Kliknij ikonę **Impersonuj** obok wybranego użytkownika.
3. System otworzy sesję w kontekście tego użytkownika — widoczny jest baner informujący o trybie impersonacji.
4. Aby zakończyć, kliknij **Powrót do konta SA** w banerze.

> **Uwaga:** Każda sesja impersonacji jest rejestrowana w logu aktywności (data, kto, kogo).

---

## 8. Kopie zapasowe

### Kopie systemowe
- **Panel SA → Kopie zapasowe** — pełna kopia bazy danych (wszystkie kluby).
- Tworzenie ręczne: kliknij **Utwórz kopię teraz** — generowany jest plik `.sql.gz`.
- Harmonogram automatyczny: codziennie o 03:00 (cron).
- Retencja: 30 ostatnich kopii (konfigurowalne).

### Pobieranie i usuwanie
- Lista kopii z datą, rozmiarem i możliwością pobrania / usunięcia.
- Pliki przechowywane w katalogu `storage/backups/` na serwerze.

### Przywracanie
- Przywracanie kopii wymaga dostępu CLI: `php cli/restore.php <plik>`.
- Przed przywróceniem system automatycznie tworzy kopię bieżącą jako punkt przywracania.

> **Bezpieczeństwo:** Dostęp do modułu kopii zapasowych posiada wyłącznie rola `super_admin`. Pobieranie plików jest chronione walidacją ścieżki (realpath).

---

## 9. Aktualizacja istniejącej instalacji

Po `git pull` (nowa wersja kodu z nowymi migracjami):

```bash
php cli/update.php --dry-run   # pokaże co zostanie zaaplikowane
php cli/update.php              # właściwy update
```

Tabela `schema_migrations` zapamiętuje co już zaaplikowano — bezpiecznie puszczać wielokrotnie. Runner skanuje:

- `database/migrations/*.sql` (migracje rdzeniowe),
- `app/Sports/*/migrations/*.sql` (migracje per-sport).

### Pierwsze uruchomienie na istniejącej bazie (bootstrap)

Jeśli baza powstała przed wprowadzeniem trackingu (`schema_migrations`), pierwszy run `cli/update.php`:

1. Wykrywa tabele `clubs` i `members` → instalacja legacy.
2. Wstawia wszystkie obecne migracje do `schema_migrations` jako **baseline** (`status=success`, `error_message='baseline (pre-existing DB)'`).
3. Kończy bez aplikowania niczego. Kolejny run zaaplikuje już tylko prawdziwie nowe migracje.

Jeśli baza jest pusta — `cli/update.php` zatrzymuje się z błędem i prosi o wcześniejsze uruchomienie `php cli/migrate.php` (fresh install ze `schema.sql`).

### Dodatkowe flagi

| Flaga | Opis |
|---|---|
| `--dry-run` | pokazuje plan, nic nie wykonuje |
| `--force` | re-aplikuje migracje już oznaczone jako `success` |
| `--only=<plik>` | aplikuje tylko jeden plik (np. `055_inpost_shipping.sql` lub `Sports/Football/001_football.sql`) |

Wszystkie operacje logowane do `storage/logs/migrations.log`.

## 10. Diagnostyka integracji (health check)

`cli/test_integrations.php` to diagnostyczny skrypt, ktory sprawdza per klub wszystkie skonfigurowane integracje (Stripe / Przelewy24 / PayU / Tpay / InPost / Google Calendar / federacje PZPN/PZSS/PZKosz/PZLA/...) wywolujac `testConnection()` na adapterach.

### Najczestsze uzycia

```bash
# Pelny przeglad wszystkich klubow
php cli/test_integrations.php

# Tylko jeden klub
php cli/test_integrations.php --club=42

# Tylko jedna integracja (wszystkie kluby)
php cli/test_integrations.php --integration=stripe

# Output JSON (do parsowania)
php cli/test_integrations.php --json
```

### Flagi

| Flaga | Opis |
|---|---|
| `--club=N` | sprawdz tylko klub o id=N |
| `--integration=stripe\|p24\|payu\|tpay\|inpost\|gcal\|federation` | tylko wybrana grupa |
| `--verbose` | pokaz szczegoly (`account_id`, `organization_id`, `last_sync_at`) |
| `--json` | output w formacie JSON (zamiast human-friendly) |
| `--fail-on-error` | exit 1 gdy ktorakolwiek integracja fail (do cron / CI) |
| `--timeout=N` | timeout HTTP per call (default 5s) |

Exit codes: `0` = OK, `1` = co najmniej 1 fail (gdy `--fail-on-error`), `2` = init error.

### Cron setup

Co godzine sprawdzaj wszystkie integracje i alertuj mailem gdy ktoras zerwie:

```cron
0 * * * * /opt/plesk/php/8.2/bin/php /var/www/clubdesk/cli/test_integrations.php --fail-on-error --json > /tmp/clubdesk-health.json 2>&1 || mail -s "ClubDesk: zerwana integracja" admin@klubdesk.pl < /tmp/clubdesk-health.json
```

Alternatywa codzienna o 6:00 z pelnym raportem human-readable do logu:

```cron
0 6 * * * /opt/plesk/php/8.2/bin/php /var/www/clubdesk/cli/test_integrations.php >> /var/log/clubdesk-integrations.log 2>&1
```

## 11. Sync ze zgloszeniami w Todoist

System `support_reports` (zgloszenia bledow i propozycji od uzytkownikow klubowych
oraz portal-memberow) jest zsynchronizowany dwukierunkowo z Todoist:

- **ClubDesk → Todoist** (push, online):
  - Formularz `/support/report` tworzy zadanie w projekcie ClubDesk.pl.
  - Zmiana statusu na `resolved`/`wont_fix`/`duplicate` w `/admin/support` zamyka task w Todoist.
  - Zmiana na `in_progress` dodaje komentarz "Przyjete do realizacji przez {admin}".
  - Reopen (status -> `new`) otwiera task ponownie.
- **Todoist → ClubDesk** (pull, cron co 5 min):
  - Skrypt `cli/todoist_sync_status.php` polluje tasky z `support_reports.todoist_task_id IS NOT NULL`.
  - Jesli task w Todoist `is_completed = true` → lokalnie `status = resolved`, `resolution_notes = "Closed in Todoist"`.
  - Jesli task zwroci 404 (usuniety) → `status = resolved`, `resolution_notes = "Task deleted in Todoist"`.
  - Aktualizuje `todoist_synced_at` przy kazdym sprawdzeniu, `todoist_sync_error` na bledach API.

### Konfiguracja

Token Todoista wpisz w `config/todoist.local.php` (plik gitignored):

```php
<?php
return [
    'api_token'  => 'twoj-personal-token-tutaj',
    'project_id' => '6gcqjmqj6QM9hQ2x', // ClubDesk.pl
];
```

Token wygenerujesz w Todoist: Settings → Integrations → Developer → API token.

### Cron setup

Co 5 min sprawdzaj tasky Todoist i synchronizuj status do bazy:

```cron
*/5 * * * * /opt/plesk/php/8.2/bin/php /var/www/vhosts/portal.clubdesk.pl/httpdocs/cli/todoist_sync_status.php >> /var/log/clubdesk-todoist-sync.log 2>&1
```

### Flagi CLI

| Flaga | Opis |
|---|---|
| `--dry-run` | pokaz co zostanie zmienione, nie zapisuj |
| `--verbose` | log per task (przydatne do debugu) |
| `--limit=N` | sprawdz maksymalnie N tasków (default: wszystko, batch po 50) |

Exit codes: `0` = OK, `1` = byly bledy API, `2` = blad inicjalizacji klienta.

### Logi

- Cron output: `/var/log/clubdesk-todoist-sync.log` (zalecane)
- Per-ticket error: kolumna `support_reports.todoist_sync_error` (widoczna w `/admin/support` jako ikona warning)

### Edge cases

- Jesli token Todoista nie skonfigurowany — cron exit 0 z komunikatem `skipped: not configured`.
- Rate limit Todoist: ~450 req/min. Skrypt batchuje po 50 z lekkim throttle ~50ms.
- Konflikt: lokalnie zmieniony status na `resolved` → push do Todoist robi `tasks/{id}/close`. Jesli sie nie uda, blad ladowany do `todoist_sync_error` ale UI nie crashuje.
- Idempotentnosc: jesli task wciaz `is_completed = false` w Todoist a lokalnie juz `resolved`, skrypt **nie** wraca lokalnie do `in_progress` (zostawiamy admin decision).

