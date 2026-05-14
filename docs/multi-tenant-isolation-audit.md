# Audyt multi-tenant izolacji — porownanie z Billu-System i Hovera.app-sys

Data: 2026-05-14
Branch: `claude/multi-tenant-isolation`

## 1. Analiza repozytoriow referencyjnych

### Hovera.app-sys (Laravel 11 + Filament 3, PHP 8.3)

Hovera to multi-tenant SaaS dla pensjonatow koni — kazda stajnia dostaje
DEDYKOWANA baze MySQL i DEDYKOWANEGO usera MySQL.

Kluczowe komponenty:

- `app/Tenancy/TenantManager.php` — singleton:
  - `current()`, `hasTenant()`, `tenantOrFail()`
  - `setCurrent(Tenant)` — konfiguruje `tenant` connection i czysci cache PDO
  - `forget()` — odlacza tenant context
  - **`execute(Tenant $tenant, callable $cb)`** — block-scoped switch z gwarancja przywrocenia poprzedniego stanu (uzywane przez queue jobs i super-admin impersonation)
- `app/Tenancy/Provisioner.php`:
  - `provision()` — `CREATE DATABASE` + `CREATE USER` + `GRANT` tylko na tenant_db.* + migracje (lub schema dump dla speed: ~5s vs ~5min)
  - `destroy()` — `DROP DATABASE` + `DROP USER` po soft-delete grace period
  - Uzywa oddzielnego `provisioner` connectionu, ktore jako jedyne ma `CREATE/DROP DATABASE` i `CREATE USER` granty
- `app/Providers/TenancyServiceProvider.php` — binduje TenantManager i Provisioner jako singletony
- Console commands: `TenantCreateCommand`, `TenantsListCommand`, `TenantsMigrateCommand`, `TenantCleanupOrphansCommand`, `TenantDumpSchemaCommand`, `SnapshotTenantHealthCommand`

Wniosek: Hovera ma **natywna izolacje na poziomie MySQL** — kazdy tenant
ma swojego MySQL usera z grantami WYLACZNIE na swoja baze. Nawet jesli
aplikacja ma SQL injection, atakujacy nie wyjdzie poza baze tenanta,
bo MySQL go nie wpusci.

### Billu-System (PHP MVC, PHP 8.1+, MySQL)

Billu to system fakturowy z hierarchia "office" -> "client". Architektonicznie
bardzo podobny do ClubDesk (custom PHP MVC, PDO, brak frameworka).

Kluczowe znaleziska:

- `src/Core/Database.php` — singleton PDO, **brak natywnego tenant scopingu** na poziomie bazy
- `src/Models/Client.php` — entity model z `FILLABLE` / `ADMIN_FILLABLE` whitelist (mass-assignment protection), per-request memoization, **brak base abstract klasy scopujacej** — kazdy model filtruje recznie po `client_id`/`office_id`
- `src/Core/Crypto.php` — AES-256-GCM, klucz wyprowadzony z `APP_SECRET_KEY` przez **HKDF-SHA256 z kontekstem nazwanym** (`'sftp.password'`, `'backup.codes'`)
- Wzorzec rerouted-by-context HKDF — bardzo dobry — pozwala roznicowac klucze per-use-case bez przechowywania N kluczy. **Ale Billu uzywa kontekstu nazwanego, nie per-tenant.**

Wniosek: Billu = shared-schema z manualnym filtrowaniem — **slabsze** niz nasz
`ClubScopedModel` (centralna abstrakcja z auto-inject `club_id`). Jego mocna
strona to HKDF-by-context dla kluczy szyfrowania.

## 2. Stan ClubDesk przed PR

Dobre rzeczy:

- `App\Helpers\ClubContext` — session-scoped current/super-admin, `setFromSubdomain()` (przelacza tenant przez `azs-warszawa.clubdesk.pl`)
- `App\Models\ClubScopedModel` — abstrakcyjny base, auto-WHERE `club_id`, auto-inject przy `insert()`, blokada zmiany `club_id` w `update()` (anti-IDOR move-to-other-club)
- 174+ migracji z `FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE`
- `/admin/audit/isolation` (`AdminAuditController::isolation`) — runtime checks: orphans, NULL club_id, cross-club joins, member_sports vs club_sports cross-club
- 53+ model'i extends `ClubScopedModel`

Luki vs Hovera/Billu:

1. **`withoutScope()` jest "ciche"** — 14+ wywolan w kodzie, **brak auditu** kto, kiedy, z jakiej tabeli i z jakiej linii omija scope
2. **Brak block-scoped tenant switch** — odpowiednik `TenantManager::execute()` z Hovera (bezpieczne tymczasowe przelaczenie tenantu z gwarancja restore w `finally`)
3. **Pojedynczy globalny klucz szyfrowania** — dump bazy ujawnia plaintexty wszystkich klubow tym samym kluczem (Billu wzorzec HKDF derive-by-context zaadaptowany do per-tenant by to ograniczyl)
4. **Brak per-tenant backupu** — `mysqldump` calej bazy, brak narzedzia do eksportu danych jednego klubu na zadanie (GDPR right-to-portability + zarzadzanie incydentem)
5. **`requireForRead()` / hard-fail guard** — brak dedykowanego API dla kodu, ktory NIGDY nie powinien dzialac bez aktywnego tenanta

## 3. Roznice — co Billu/Hovera robia inaczej

| Aspekt | Hovera | Billu | ClubDesk (przed) | ClubDesk (po) |
|---|---|---|---|---|
| Izolacja danych | DB-per-tenant + MySQL user/grants | shared schema, recznie | shared schema, `ClubScopedModel` | bez zmian arch + audit bypass |
| Cross-tenant query | Wymaga `execute()` na innym tenancie | brak | `withoutScope()` (ciche) | `withoutScope()` + audit do `tenant_access_log` |
| Block-scoped tenant switch | tak — `TenantManager::execute()` | brak | brak | tak — `ClubContext::execute()` |
| Encryption | per-tenant DB credentials | HKDF derive-by-context (nazwany) | jeden master key | HKDF derive-by-club (`encryptForClub()`) |
| Backup | natywny dump per-DB | nie wiadomo | jeden dump calej bazy | `cli/backup_club.php` per-club |
| Audit isolation | `SnapshotTenantHealthCommand` | nie wiadomo | `/admin/audit/isolation` (orphans, NULL) | + `/admin/audit/access-log` (cross-tenant access) |
| Hard-fail guard | `tenantOrFail()` | brak | `ClubContext::require()` | + `ClubContext::requireForRead($context)` z severity |

## 4. Wzmocnienia zaimplementowane w tym PR

### A. Audit cross-tenant access (`tenant_access_log`)

Migracja: `database/migrations/066_tenant_access_log.sql`
Model:     `app/Models/TenantAccessLogModel.php`

Tabela zapisuje **kazde wywolanie `withoutScope()`** (oraz insert/update/delete
bez aktywnego scope) z metadanymi:

- `user_id`, `username`, `is_super_admin`
- `active_club_id` w momencie bypassu
- `table_name` + `operation` (read/write/delete/count)
- `caller_file`, `caller_line`, `caller_class` (z `debug_backtrace`)
- `request_path`, `request_method`
- `severity` (info dla super-admina, warning dla zwyklego usera)

Rotacja: `TenantAccessLogModel::pruneOlderThan(90)` (mozna podpiac pod cron).
Audit jest **non-blocking** — kazdy throw w logowaniu jest tlumiony, audit
NIGDY nie crashuje requestu uzytkowego.

UI: `/admin/audit/access-log` (route + view + controller action).
Pokazuje top-bypasses last 7d + paginowana lista wpisow.

### B. `ClubContext::execute()` — block-scoped tenant switch (Hovera pattern)

```php
ClubContext::execute($otherClubId, function () use ($id) {
    return (new MemberModel())->findById($id);
});
```

Bezpiecznie zachowuje poprzedni `club_id` (lub `null`), gwarantuje restore
w `finally` nawet jesli callback rzuci. Eliminuje boilerplate
`save current → set → try → finally → restore` w kodzie cron-ow,
queue-jobow i admin impersonation.

### C. `ClubContext::requireForRead($context)`

Hard-fail guard z bardziej semantycznym komunikatem niz generyczny
`require()`. Komunikat zawiera kontekst (np. `'MemberPortalController::dashboard'`),
co ulatwia triage w `ErrorMonitor` i SIEM-ie.

### D. Per-club HKDF encryption (`encryptForClub()` / `decryptForClub()`)

Wzorzec inspirowany Billu (`Crypto::encrypt` z HKDF derive-by-context),
zaadaptowany na per-tenant:

```php
$encrypted = Encryption::encryptForClub($pesel, $clubId);
$plain     = Encryption::decryptForClub($encrypted, $clubId);
```

- Klucz: `HKDF-SHA256(master_key, info = "clubdesk:club:{id}", 32 bajty)`
- Format ciphertext: `base64(0x01 . nonce[12] . ciphertext . tag[16])` — prefiks `0x01` to wersja formatu
- **Wsteczna kompatybilnosc**: stary ciphertext (bez prefiksu wersji) jest detect-owany w `decryptForClub()` i deszyfrowany przez `decrypt()` z master key. Migracja istniejacych szyfrowanych pol moze isc lazy.
- Wartosc bezpieczenstwa: dump bazy klubu A — bez `club_id` ofiary i master key — **nie** pozwala zdeszyfrowac plaintextow klubu B. Master key sam nie wystarcza.

Funkcje nieinwazyjne — istniejacy kod uzywa nadal `encrypt()`/`decrypt()`.
Nowe pola wrazliwe (lub migracja w czasie) moga przelaczyc sie na
`encryptForClub()` punktowo.

### E. `cli/backup_club.php` — per-tenant backup

```bash
php cli/backup_club.php 42              # backup klubu 42 (gzip default)
php cli/backup_club.php 42 --no-gzip
php cli/backup_club.php --all           # wszystkie aktywne kluby
```

- Auto-discover tabel z kolumna `club_id` przez `information_schema` (nie wymaga sync z `AdminAuditController::CLUB_SCOPED_TABLES`)
- Per tabela: `mysqldump --where="club_id=N"` -> wynikowy plik zawiera WYLACZNIE dane jednego klubu
- Haslo MySQL przekazane przez env `MYSQL_PWD` (nie ujawniane w `argv`)
- Wyjscie: `storage/backups/club_<id>_<name>_<ts>.sql[.gz]`
- Bezpieczne wzgledem missing-tables i mysqldump errors (continue + warn)

Zastosowania:
- GDPR right-to-portability (eksport danych jednego klubu)
- Migracja klubu off-platform
- Incident-response: forensic snapshot konkretnego klubu

### F. Audit instrumentation `ClubScopedModel`

`withoutScope()`, `insert()` bez scope, `update()` bez scope, `delete()` bez
scope — wszystkie loguja do `tenant_access_log` przez `logBypassOnce()`.

Dodano `ClubScopedModel::disableAudit()` / `enableAudit()` dla kontrolowanego
batchowego bypassu (np. cron, ktory swiadomie iteruje cross-tenant w petli i
nie chce zalewac logu — wlacza wpis przed petla, wylacza audit, iteruje,
wlacza audit z powrotem).

## 5. Wzmocnienia SWIADOMIE pominiete

| Pomysl | Powod pominiecia |
|---|---|
| **Separate DB per tenant (Hovera)** | Architektoniczny rewrite. 174+ migracji × N tenantow = N × duration migracji, N × backupy do orkiestracji, N × monitoring. ClubDesk ma model "self-host" gdzie klub stawia wlasna instancje — to ALTERNATYWNA droga do izolacji, ktora juz istnieje. Dla shared-host SaaS koszt nie uzasadnia korzysci dopoki nie mamy enterprise tenant'ow z compliance requirement. |
| **DB-level VIEW per tabela ze session var `@club_id`** | Defense-in-depth atrakcyjne, ale 100+ tabel klubowych → 100+ VIEW do utrzymania, kazda nowa migracja wymaga aktualizacji VIEW. `ClubScopedModel` + nowy audit access-log daja 80% wartosci za 5% kosztu. Mozemy wrocic do tego pomyslu jesli audit-log pokaze ze ludzie nagminnie ignoruja `ClubScopedModel`. |
| **MySQL Row-Level Security (RLS)** | MySQL natywnie tego nie ma (Postgres ma). Wymagalo by switchu na MariaDB lub fakowania przez VIEW + DEFINER — patrz punkt wyzej. |
| **Per-tenant MySQL user/grants** | Patrz "Separate DB per tenant" — to samo. Dodatkowo wymaga osobnego procesu provisioningu MySQL userow. |
| **Auto-encrypt wszystkich pol PII per-club przy pisaniu** | Wymaga refactoringu kazdego pola PII w 96+ modelach + lazy migration starych danych. Wartosc dodana = `encryptForClub()` jest dostepne — moze byc adoptowane punktowo (np. nowa kolumna `members.pesel_v2`). Pozostawiam w gestii architekta jako follow-up. |
| **Cron pruning `tenant_access_log`** | Funkcja `pruneOlderThan(90)` jest gotowa, ale uzytkownik decyduje sam kiedy ja podpiac (w `cli/alerts_cron.php` lub osobnym workerze). Nie chcemy zmieniac roznych cronow w tym PR. |
| **Rewrite `Database` na `Database::pdoForClub($clubId)`** | Wymaga zmiany wszystkich `Database::pdo()` call sites (setki). Brak korzysci dopoki nie idziemy na DB-per-tenant. |

## 6. Migracja i deployment

```bash
# 1. Zaaplikuj migracje
php cli/update.php

# 2. (opcjonalnie) Pierwszy backup per-club jako smoke test
php cli/backup_club.php 1

# 3. Sprawdz endpoint
curl -i /admin/audit/access-log    # po zalogowaniu jako super-admin
```

## 7. Testowanie manualne (TODO przed merge'em)

- [ ] `php cli/update.php` -> migracja 066 idzie zielono
- [ ] Wywolaj `(new MemberModel())->withoutScope()->findAll()` jako super-admin -> sprawdz `tenant_access_log` ma wpis z `severity=info`
- [ ] Wywolaj `withoutScope()` jako zwykly user -> wpis ma `severity=warning`
- [ ] `/admin/audit/access-log` renderuje sie i pokazuje wpisy
- [ ] `php cli/backup_club.php 1` tworzy `storage/backups/club_1_*.sql.gz` zawierajacy tylko `club_id=1`
- [ ] `Encryption::encryptForClub('test', 1)` -> `decryptForClub(..., 1)` == `'test'`
- [ ] `Encryption::decryptForClub(..., 2)` (zly clubId) zwraca `null`
- [ ] Stary ciphertext `Encryption::encrypt('test')` -> `decryptForClub(..., $anyId)` == `'test'` (backward compat fallback)
- [ ] `ClubContext::execute(99, fn() => ClubContext::current())` zwraca 99, a po wyjsciu `current()` to poprzednia wartosc
