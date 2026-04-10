# KlubSportowy — Multi-Sport Club Management Portal

Wielosportowy, wieloklubowy portal SaaS do zarządzania klubami sportowymi.

PHP 8.1+ / MySQL 8 / Bootstrap 5 / architektura MVC bez frameworka.

## Koncepcja

Jedna platforma obsługuje wiele klubów (multi-tenant: `club_id`),
a każdy klub może prowadzić **wiele sekcji sportowych jednocześnie**
(np. piłka nożna + koszykówka + sekcja wrotkarska). Sporty są
**pierwszoklasową abstrakcją** — dodanie nowego sportu to stworzenie
manifestu pluginu w `app/Sports/<Nazwa>/manifest.php`, bez zmian
w rdzeniu aplikacji.

### Wspierane sporty (seed)

| Sport | Federacja | Typ |
|---|---|---|
| Piłka nożna | PZPN | drużynowy |
| Koszykówka | PZKosz | drużynowy |
| Siatkówka | PZPS | drużynowy |
| Strzelectwo | PZSS | indywidualny |
| Lekka atletyka | PZLA | indywidualny |
| Hokej na lodzie | PZHL | drużynowy |
| Piłka ręczna | PZPR | drużynowy |
| Tenis | PZT | indywidualny |
| Pływanie | PZP | indywidualny |
| Wrotkarstwo | PZW | indywidualny |
| Judo | PZJ | indywidualny |
| Karate | PZKarate | indywidualny |

Dodanie kolejnego sportu (np. lekkoatletyka, snooker, e-sport) to:
1. `INSERT INTO sports` + opcjonalnie `INSERT INTO federations`
2. `app/Sports/<Nazwa>/manifest.php` z routami i nawigacją
3. `app/Sports/<Nazwa>/migrations/001_*.sql` z ewentualnymi tabelami pluginu

## Architektura

```
KlubSportowy/
├── public/                 # document root
│   ├── index.php           # front controller + router
│   ├── .htaccess           # rewrite do index.php
│   ├── css/app.css
│   └── js/app.js
├── config/
│   ├── app.php             # app config
│   └── database.php        # DB template (real creds w .local.php)
├── database/
│   └── schema.sql          # pełny schemat + seed data
├── app/
│   ├── Helpers/            # Auth, Session, Router, ClubContext, SportContext...
│   ├── Models/             # BaseModel, ClubScopedModel + encje
│   ├── Controllers/        # Auth, Dashboard, Admin, Members, Fees, Events...
│   ├── Views/              # Bootstrap 5 + układ main/auth/none
│   └── Sports/             # moduły per-sport (manifest + controllers + views)
│       ├── Shooting/
│       ├── Football/
│       ├── Basketball/
│       ├── Volleyball/
│       ├── Rollerskating/
│       └── Athletics/
├── storage/                # logs, uploads, backups (git-ignored)
└── cli/
    ├── migrate.php         # instalacja schematu
    └── seed.php            # re-seed danych testowych
```

### Multi-tenancy

Każde żądanie ma aktywny kontekst klubu w sesji (`ClubContext`). Modele
dziedziczące z `ClubScopedModel` **automatycznie** filtrują zapytania
po `club_id` i dodają go przy insercie. Super admin może wyłączyć scope
przez `->withoutScope()`. Subdomena (np. `azs-warszawa.klubsportowy.pl`)
automatycznie przełącza kontekst klubu na podstawie
`club_customization.subdomain`.

### Multi-sport

Klub aktywuje sekcje z `sports` przez tabelę `club_sports`. Zawodnik
przypisywany jest do konkretnej sekcji przez `member_sports`. Stawki
opłat, wydarzenia i licencje mogą być per-sport lub ogólnoklubowe.
Wyniki wydarzeń zawierają kolumnę JSON `extra` przechowującą
specyfikę danego sportu (np. 10 strzałów dla strzelectwa,
asysty/zbiórki dla koszykówki).

### Plugin architecture

`App\Helpers\SportModuleLoader` skanuje `app/Sports/*/manifest.php`
i rejestruje trasy + nawigację w routerze/layoucie. Każdy manifest zwraca
tablicę zgodną z kontraktem:

```php
return [
    'key'        => 'shooting',
    'name'       => 'Strzelectwo',
    'federation' => 'PZSS',
    'features'   => ['weapons','ammo','pzss_license','judges'],
    'routes'     => [ ['GET', '/shooting/weapons', [Controller::class, 'index']], ... ],
    'nav'        => [ ['label' => 'Broń', 'icon' => 'bi-bullseye', 'url' => 'shooting/weapons'], ... ],
    'migrations' => __DIR__ . '/migrations',
];
```

## Instalacja

1. **Baza danych:**
   ```bash
   mysql -u root -p -e "CREATE DATABASE klubsportowy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p klubsportowy < database/schema.sql
   ```

2. **Konfiguracja:**
   ```bash
   cp config/database.php config/database.local.php
   # edytuj config/database.local.php — uzupełnij host/user/pass
   ```

3. **Uprawnienia (Linux):**
   ```bash
   chmod -R 775 storage/
   ```

4. **Document root → `public/`**. Na Plesku wybierz `public/` jako web root.
   Lokalnie:
   ```bash
   php -S localhost:8080 -t public/
   ```

5. **Zaloguj się:** `admin / Admin1234!` (zmień hasło natychmiast).

## Panel administracyjny (super admin)

- `/admin/dashboard` — metryki platformy
- `/admin/clubs` — zarządzanie klubami
- `/admin/sports` — katalog sportów
- `/admin/plans` — plany subskrypcyjne

## Panel klubu (po zalogowaniu + wybraniu klubu)

- `/dashboard` — statystyki klubu
- `/sports` — aktywacja sekcji sportowych
- `/members` — zawodnicy (z przypisaniem do sekcji)
- `/fees` — finanse (wpłaty)
- `/fees/rates` — stawki opłat
- `/events` — wydarzenia (mecze / zawody / treningi)

## Role w klubie (`user_clubs.role`)

| Rola | Opis |
|---|---|
| `zarzad` | Pełny dostęp w klubie |
| `trener` | Treningi, wyniki, zawodnicy |
| `instruktor` | Zajęcia, uczestnicy |
| `sedzia` | Wyniki, rozgrywki |
| `lekarz` | Badania lekarskie |
| `ksiegowy` | Finanse |

Użytkownik może mieć **kilka ról** w tym samym klubie oraz **dostęp do wielu klubów**.

## Roadmapa

### Phase 1 (this commit)
- [x] Multi-klub, multi-sport rdzeń
- [x] Auth + rejestracja klubu
- [x] Zawodnicy z przypisaniem do sekcji
- [x] Finanse (stawki + wpłaty)
- [x] Wydarzenia (mecze/zawody/treningi) — generyczne
- [x] Subskrypcje SaaS (trial/basic/standard/premium)
- [x] 6 sportów w katalogu + 6 manifestów pluginów
- [x] Subdomeny klubowe
- [x] Custom branding per-klub

### Phase 2 — porty z ShootingClubMng
- [ ] Moduł strzelecki: broń, amunicja, licencje PZSS, sędziowie
- [ ] Panel zawodnika (self-service portal)
- [ ] Badania lekarskie + alerty
- [ ] Szablony e-mail i kolejka wysyłki
- [ ] SMS (Twilio / SMSAPI)
- [ ] 2FA (TOTP)
- [ ] Kopie zapasowe

### Phase 3 — sport-specific
- [ ] Piłka nożna: drużyny, mecze, kartki, transfery
- [ ] Koszykówka: statystyki per-zawodnik
- [ ] Siatkówka: sety
- [ ] Wrotkarstwo: pomiary czasu

### Phase 4 — platform
- [ ] Public API v1 (JSON, klucze API per klub)
- [ ] GDPR (eksport, anonimizacja)
- [ ] Reklamy (system-wide / per-klub)
- [ ] Raporty PDF
- [ ] Integracje z systemami federacji

## Licencja

Proprietary. &copy; 2026 PrzemekPrzemo.
