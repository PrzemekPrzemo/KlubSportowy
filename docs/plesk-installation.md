# Instrukcja wdrożenia KlubSportowy na Plesk Panel

## Domena: portal.clubdesk.pl

---

## 1. Wymagania serwera

| Komponent | Minimum | Zalecane |
|---|---|---|
| PHP | 8.1 | 8.2+ |
| MySQL | 8.0 | 8.0+ |
| Pamięć RAM | 1 GB | 2 GB+ |
| Dysk | 5 GB | 20 GB+ |
| SSL | Let's Encrypt | Let's Encrypt (auto) |

### Wymagane rozszerzenia PHP:
```
pdo_mysql, mbstring, json, gd, zip, intl, openssl, curl, fileinfo
```

### Opcjonalne (dla pełnej funkcjonalności):
```
redis, opcache
```

---

## 2. Konfiguracja domeny w Plesk

### 2.1 Dodaj domenę
1. Plesk → **Websites & Domains** → **Add Domain**
2. Domena: `portal.clubdesk.pl`
3. Document root: **ustawić na `public/`** (KRYTYCZNE!)
4. Hosting type: Website hosting
5. PHP version: **8.2** (lub 8.1 minimum)

### 2.2 SSL Certificate
1. Plesk → **portal.clubdesk.pl** → **SSL/TLS Certificates**
2. Klik **Let's Encrypt** → zaznacz `portal.clubdesk.pl`
3. **Włącz**: "Redirect from http to https"
4. **Włącz**: "Keep secured" (HSTS)

### 2.3 PHP Settings
1. Plesk → **portal.clubdesk.pl** → **PHP Settings**
2. PHP version: **8.2-fpm** (lub 8.1-fpm)
3. Zmień:
   ```
   upload_max_filesize = 20M
   post_max_size = 25M
   memory_limit = 256M
   max_execution_time = 300
   date.timezone = Europe/Warsaw
   session.cookie_httponly = 1
   session.use_strict_mode = 1
   session.cookie_samesite = Lax
   session.cookie_secure = 1
   ```
4. Rozszerzenia: włącz `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`,
   `openssl`, `curl`, `fileinfo`
5. Zapisz

### 2.4 Document Root
1. Plesk → **portal.clubdesk.pl** → **Hosting & DNS** → **Hosting Settings**
2. Document root: zmień na `/httpdocs/public`
   (jeśli repo klonujesz do `/httpdocs/`, document root = `/httpdocs/public`)

---

## 3. Baza danych MySQL

### 3.1 Utwórz bazę
1. Plesk → **Databases** → **Add Database**
2. Database name: `clubdesk_prod`
3. Database server: localhost
4. User: `clubdesk_user`
5. Password: **silne hasło (32+ znaków)** — zapisz!

### 3.2 Import schematu
W Plesk SSH lub phpMyAdmin:
```bash
mysql -u clubdesk_user -p clubdesk_prod < /var/www/vhosts/portal.clubdesk.pl/httpdocs/database/schema.sql
```

Lub przez phpMyAdmin:
1. Plesk → Databases → phpMyAdmin
2. Wybierz `clubdesk_prod`
3. Import → wybierz `database/schema.sql` → Execute

---

## 4. Wgranie kodu

### Opcja A: Git (zalecane)
```bash
cd /var/www/vhosts/portal.clubdesk.pl/httpdocs
git clone https://github.com/PrzemekPrzemo/KlubSportowy.git .
git checkout main
```

### Opcja B: Upload ZIP
1. Pobierz ZIP z GitHub: Code → Download ZIP
2. Plesk → File Manager → `/httpdocs/`
3. Upload ZIP → Extract
4. Upewnij się że `public/index.php` jest w `/httpdocs/public/index.php`

### Opcja C: FTP
1. Plesk → FTP Access → credentials
2. FileZilla → upload cały projekt do `/httpdocs/`

---

## 5. Konfiguracja aplikacji

### 5.1 Database config
```bash
cd /var/www/vhosts/portal.clubdesk.pl/httpdocs
cp config/database.php config/database.local.php
```

Edytuj `config/database.local.php`:
```php
<?php
return [
    'host'     => 'localhost',
    'port'     => 3306,
    'dbname'   => 'clubdesk_prod',
    'username' => 'clubdesk_user',
    'password' => 'TWOJE_SILNE_HASLO',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
```

### 5.2 App config
```bash
cp config/app.php config/app.local.php
```

Edytuj `config/app.local.php`:
```php
<?php
return [
    'app_name'    => 'ClubDesk',
    'app_version' => '1.0.0',
    'debug'       => false,       // ZAWSZE false na produkcji!
    'timezone'    => 'Europe/Warsaw',
    'locale'      => 'pl_PL',
    'base_url'    => 'https://portal.clubdesk.pl',

    'session_lifetime' => 7200,
    'root_path'   => dirname(__DIR__),
    'view_path'   => dirname(__DIR__) . '/app/Views',
    'upload_path' => dirname(__DIR__) . '/storage/uploads',

    'default_federation_country' => 'PL',
    'sentry_dsn'  => '',          // Opcjonalnie: Sentry DSN do monitoringu
];
```

### 5.3 Encryption key (KRYTYCZNE!)
```bash
php cli/generate-key.php
```
Skopiuj wygenerowany klucz i utwórz `config/encryption.local.php`:
```php
<?php
return [
    'key'    => 'WYGENEROWANY_KLUCZ_BASE64',
    'cipher' => 'aes-256-gcm',
];
```

### 5.4 Globalne ustawienia w bazie
Po imporcie schematu, zaloguj się jako admin i ustaw:
- **Settings → base_domain**: `clubdesk.pl`
- **Settings → system_name**: `ClubDesk`

---

## 6. Uprawnienia plików

```bash
cd /var/www/vhosts/portal.clubdesk.pl/httpdocs

# Storage: writable by PHP
chmod -R 775 storage/
chown -R www-data:www-data storage/    # lub: psacln:psaserv na Plesk

# Public uploads: writable
chmod -R 775 public/uploads/
chown -R www-data:www-data public/uploads/

# Config local files: readable only by owner
chmod 640 config/database.local.php
chmod 640 config/app.local.php
chmod 640 config/encryption.local.php

# Logs directory
mkdir -p storage/logs storage/backups storage/cache
chmod -R 775 storage/
```

Na Plesk owner to zazwyczaj `<subscription_user>:psaserv`:
```bash
OWNER=$(stat -c '%U' /var/www/vhosts/portal.clubdesk.pl/)
chown -R $OWNER:psaserv storage/ public/uploads/
```

---

## 7. Composer (opcjonalnie)

Jeśli chcesz PDF (mpdf):
```bash
cd /var/www/vhosts/portal.clubdesk.pl/httpdocs
composer install --no-dev --optimize-autoloader
```

Jeśli composer nie jest dostępny w Plesk SSH:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php composer.phar install --no-dev --optimize-autoloader
rm composer-setup.php
```

---

## 8. Apache/Nginx rewrite

### Apache (.htaccess — już w repo)
Plik `public/.htaccess` jest gotowy. Upewnij się że w Plesk:
- **Apache & nginx Settings** → **Apache**: AllowOverride All
- Lub w **Additional Apache directives**:
```apache
<Directory /var/www/vhosts/portal.clubdesk.pl/httpdocs/public>
    AllowOverride All
    Options -Indexes
</Directory>
```

### Nginx (jeśli Plesk używa nginx as reverse proxy)
Plesk → **portal.clubdesk.pl** → **Apache & nginx Settings** →
**Additional nginx directives**:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ /\. {
    deny all;
}
```

---

## 9. CRON Jobs

Plesk → **Scheduled Tasks** → dodaj 3 zadania:

### Email worker (co minutę)
```
* * * * * /opt/plesk/php/8.2/bin/php /var/www/vhosts/portal.clubdesk.pl/httpdocs/cli/email_worker.php >> /var/www/vhosts/portal.clubdesk.pl/httpdocs/storage/logs/email_cron.log 2>&1
```

### Alerty (codziennie o 6:00)
```
0 6 * * * /opt/plesk/php/8.2/bin/php /var/www/vhosts/portal.clubdesk.pl/httpdocs/cli/alerts_cron.php >> /var/www/vhosts/portal.clubdesk.pl/httpdocs/storage/logs/alerts_cron.log 2>&1
```

### Backup (codziennie o 3:00)
```
0 3 * * * /opt/plesk/php/8.2/bin/php /var/www/vhosts/portal.clubdesk.pl/httpdocs/cli/backup.php >> /var/www/vhosts/portal.clubdesk.pl/httpdocs/storage/logs/backup_cron.log 2>&1
```

> **Uwaga**: Ścieżka PHP na Plesk to zazwyczaj `/opt/plesk/php/8.2/bin/php`.
> Sprawdź: `which php` lub `ls /opt/plesk/php/*/bin/php`

---

## 10. Pierwsze logowanie

1. Otwórz: `https://portal.clubdesk.pl`
2. Powinieneś zobaczyć **Landing Page**
3. Kliknij **Zaloguj** → login: `admin`, hasło: `Admin1234!`
4. **NATYCHMIAST zmień hasło admina!**
5. Włącz **2FA** (Konto → 2FA TOTP)
6. Ustaw **Settings** w panelu admina:
   - base_domain: `clubdesk.pl`
   - system_name: `ClubDesk`
   - SMTP (globalny lub per-klub)

---

## 11. Konfiguracja SMTP (wysyłka maili)

### Globalny SMTP (admin → Settings)
W tabeli `settings` lub przez panel admin:
```
smtp_host      = smtp.twojserwer.pl
smtp_port      = 587
smtp_secure    = tls
smtp_user      = noreply@clubdesk.pl
smtp_pass_enc  = haslo_smtp
```

### Per-klub SMTP
Każdy klub konfiguruje własny SMTP w:
**Ustawienia klubu → SMTP / SMS**

---

## 12. Subdomeny per-klub

Jeśli chcesz subdomeny (np. `azs-warszawa.clubdesk.pl`):

1. Plesk → **Add Subdomain**: `*.clubdesk.pl` (wildcard)
   Lub: dodawaj subdomeny pojedynczo per-klub
2. Wildcard DNS: w panelu DNS dodaj `*.clubdesk.pl` → A record → IP serwera
3. W ClubDesk: admin konfiguruje subdomenę per-klub w **Branding → Subdomain**
4. System automatycznie wykrywa subdomenę przez `ClubContext::setFromSubdomain()`

---

## 13. Checklist po wdrożeniu

- [ ] `https://portal.clubdesk.pl` ładuje landing page
- [ ] Login admin działa (admin / Admin1234!)
- [ ] Hasło admina zmienione
- [ ] 2FA włączone dla admina
- [ ] SMTP działa (wyślij testowy email)
- [ ] SSL certyfikat aktywny (kłódka w przeglądarce)
- [ ] CRON email_worker uruchomiony
- [ ] CRON alerts_cron uruchomiony
- [ ] `debug = false` w config/app.local.php
- [ ] `config/*.local.php` NIE jest w git (sprawdź .gitignore)
- [ ] Encryption key wygenerowany i zapisany
- [ ] storage/ directories writable
- [ ] Utworzony pierwszy klub testowy
- [ ] Dodany pierwszy zawodnik
- [ ] Portal zawodnika działa (/portal/login)
- [ ] PDF raporty generują się poprawnie

---

## 14. Aktualizacja (deploy nowej wersji)

```bash
cd /var/www/vhosts/portal.clubdesk.pl/httpdocs
git pull origin main
composer install --no-dev --optimize-autoloader  # jeśli zmiany w composer.json
# Uruchom nowe migracje jeśli są:
# mysql -u clubdesk_user -p clubdesk_prod < database/migrations/XXX_new.sql
```

---

## 15. Troubleshooting

| Problem | Rozwiązanie |
|---|---|
| Biały ekran | Sprawdź `storage/logs/app.log`. Ustaw `debug = true` tymczasowo. |
| 404 na wszystkim oprócz / | Document root nie wskazuje na `public/`. Sprawdź punkt 2.4. |
| Brak wysyłki maili | Sprawdź SMTP config. Sprawdź `storage/logs/email_cron.log`. |
| Błąd bazy danych | Sprawdź `config/database.local.php`. Test: `php -r "new PDO(...);"` |
| Permission denied storage/ | `chmod -R 775 storage/` + `chown` na właściciela Plesk |
| Session wygasa za szybko | Sprawdź `session_lifetime` w config/app.local.php |
| PDF nie generuje | Uruchom `composer install` — wymaga mpdf |

---

## 16. Kontakt i support

- Repo: `github.com/PrzemekPrzemo/KlubSportowy`
- Logi: `storage/logs/app.log`
- Backup: `storage/backups/`
- Monitoring: Sentry (jeśli skonfigurowany w sentry_dsn)
