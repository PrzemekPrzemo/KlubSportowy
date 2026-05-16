# GDPR (RODO) — przewodnik dla administratora klubu

ClubDesk implementuje pełen self-service portal RODO dla członków klubów:
prawo do bycia zapomnianym (art. 17) + prawo do przenoszenia danych (art. 20)
+ obsługa pozostałych wniosków przez admin panel.

## Komponenty

| Element                                          | Plik                                              |
|--------------------------------------------------|---------------------------------------------------|
| Tabela DB                                        | `database/migrations/077_gdpr_requests.sql`       |
| Model                                            | `app/Models/GdprRequestModel.php`                 |
| Portal członka                                   | `app/Controllers/MemberGdprController.php`        |
| Admin panel                                      | `app/Controllers/AdminGdprController.php`         |
| Anonimizacja (art. 17)                           | `App\Helpers\GdprService::anonymizeMember()`      |
| Eksport ZIP (art. 20)                            | `App\Helpers\Gdpr\MemberDataExporter::export()`   |
| Cron worker — eksporty                           | `cli/process_gdpr_exports.php` (`*/5 * * * *`)    |
| Cron worker — cleanup                            | `cli/gdpr_cleanup_exports.php` (`0 3 * * *`)      |
| Storage ZIP                                      | `storage/gdpr_exports/{club_id}/{request_id}.zip` |

## Flow eksportu ZIP (art. 20)

```
[Członek] /portal/gdpr/export ──── POST ─────► gdpr_requests (status=pending, token)
    │                                                 │
    │                                                 ▼
    │                                       email "Potwierdz prosbe GDPR"
    │                                                 │
    │                                                 ▼
    └─── klika link ─► /portal/gdpr/confirm/:token
                                │
                                ▼
                       confirm() → status=in_progress
                                │
                                ├─────► processExportRequest() (synchroniczny fallback)
                                │           │
                                │           ▼
                                │   MemberDataExporter::export()
                                │   → storage/gdpr_exports/{club}/{req}.zip
                                │   → status=completed, expires=NOW()+7d
                                │   → email "gotowy do pobrania"
                                │
                                └─OR─► cron worker process_gdpr_exports.php
                                       (max 5/run, ten sam scenariusz)

[Członek] /portal/gdpr/export/:id/download
    │
    ▼
downloadExport() → readfile(storage/gdpr_exports/{club}/{req}.zip)
    + Content-Disposition: moje_dane_klubowe_{slug}_{date}.zip
    + audit log do tenant_access_log
```

## Cron — instalacja

Dopisz do crontab (jako użytkownik z dostępem do php 8.1+):

```cron
# Worker: generuj ZIP eksporty co 5 minut (max 5 na run).
*/5 * * * * /opt/plesk/php/8.3/bin/php /var/www/clubdesk/cli/process_gdpr_exports.php >> /var/log/clubdesk/gdpr_exports.log 2>&1

# Cleanup: usuń wygasłe ZIP-y (>7 dni) codziennie o 3:00.
0 3 * * * /opt/plesk/php/8.3/bin/php /var/www/clubdesk/cli/gdpr_cleanup_exports.php >> /var/log/clubdesk/gdpr_cleanup.log 2>&1
```

Jeśli używasz starszego skryptu `cli/gdpr_cleanup.php` — możesz go pozostawić
lub zastąpić nową wersją; obie iterują po tych samych wierszach `gdpr_requests`.

## Admin UI (`/admin/gdpr`)

Wymaga roli `zarzad` lub `admin`.

| Akcja                | Endpoint                                  | Metoda |
|----------------------|-------------------------------------------|--------|
| Lista wszystkich     | `/admin/gdpr`                             | GET    |
| Szczegóły wniosku    | `/admin/gdpr/:id`                         | GET    |
| Zatwierdź / odrzuć   | `/admin/gdpr/:id/process`                 | POST   |
| Force regenerate ZIP | `/admin/gdpr/:id/regenerate`              | POST   |

W widoku detail dla zakończonych eksportów widać:
- rozmiar pliku (kB)
- czas generowania (sekundy)
- data wygaśnięcia
- czy plik istnieje na dysku (cleanup detection)
- przycisk **Force regenerate** — usuwa stary ZIP + tworzy nowy (waznosc 7 dni od teraz)

## Bezpieczeństwo (defense in depth)

1. **Cross-tenant guard**: `MemberDataExporter` w każdym SELECT ma `WHERE club_id = ?`
   i waliduje że `member.club_id == $clubId` (rzuca `RuntimeException` przy mismatch).
2. **Encrypted fields**: PESEL/email/telefon odszyfrowane tylko per-request,
   nigdy nie są cache'owane jako plaintext.
3. **Path traversal**: pliki binarne (`documents/`, `photos/`) walidowane
   `realpath()` — muszą leżeć w `ROOT_PATH`.
4. **File serving**: tylko przez kontroler (`readfile` + headers), pliki ZIP są
   poza `/public/`. URL `/portal/gdpr/export/:id/download` wymaga zalogowanego
   członka, `member_id` weryfikowany przez `GdprRequestModel::findOwnedBy()`.
5. **chmod 0600** na pliku ZIP po wygenerowaniu.
6. **Audit log** każdej operacji w `tenant_access_log` (zarówno generacja jak
   download i expiration).
7. **CSRF** na wszystkich POST endpointach (`Csrf::verify()`).

## Struktura ZIP

```
moje_dane_klubowe_{klub}_{YYYY-MM-DD}.zip
├── README.txt                       (PL, opis zawartości)
├── manifest.json                    (lista plików + SHA-256 + metadane)
├── data/
│   ├── profile.json                 (PII odszyfrowane)
│   ├── payments.json
│   ├── trainings.json
│   ├── tournaments.json
│   ├── events.json
│   ├── medical.json                 (dane wrażliwe)
│   ├── consents.json
│   ├── communications.json
│   ├── achievements.json
│   ├── rankings.json
│   ├── licenses.json
│   ├── body_metrics.json
│   ├── notification_prefs.json
│   └── gdpr_requests.json
├── documents/
│   └── *.pdf                        (umowy, zaświadczenia z member_documents)
└── photos/
    └── *.jpg                        (zdjęcie profilowe)
```

Format JSON: pretty-print, UTF-8, ISO 8601 daty.

## Testy

```bash
vendor/bin/phpunit --filter MemberDataExporterTest
```

Pokrywa: smoke test ZIP, manifest + README + profile.json, SHA-256 checksumy,
cross-tenant guard, błędy dla nieistniejącego członka.

## Troubleshooting

- **"Plik eksportu nie istnieje na dysku"** w portalu członka → cron cleanup
  usunął wygasły plik, ale gdpr_requests.export_file_path nie został zerowany.
  Reset ręczny: `UPDATE gdpr_requests SET export_file_path = NULL WHERE id = X;`.
- **Worker nie odpala**: sprawdź `php_version_check.php` — wymaga >= 8.1.
  Plesk domyślnie ma `/usr/bin/php` = 7.4, użyj jawnej ścieżki `/opt/plesk/php/8.3/bin/php`.
- **Brak emaila po wygenerowaniu**: `email_queue` musi być przetwarzany przez
  `cli/email_worker.php`. Sprawdź szablon `gdpr_export_ready` w
  `email_event_catalog`.
