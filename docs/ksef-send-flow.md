# KSeF — flow wysyłki faktury (Phase 3)

> Phase 3 dokłada warstwę dispatchu do wszystkiego co wcześniej dostarczyły
> Phase 1 (konfiguracja + auth) i Phase 2 (FA(2) XML + numeracja). Tutaj
> opisana jest droga faktury od **issued** w ClubDesk do **accepted_ksef** +
> UPO XML w archiwum.

## State machine kolejki `ksef_send_queue`

```
                ┌───────────┐
                │  queued   │  ← enqueue() z UI lub API
                └─────┬─────┘
                      │  worker pobiera (FOR UPDATE SKIP LOCKED)
                      ▼
                ┌───────────┐
                │  signing  │  ← XAdESSigner::signChallenge() + InitSigned
                └─────┬─────┘
                      │  ksef_session_token zapisany
                      ▼
                ┌───────────┐
                │  sending  │  ← FA2XmlGenerator + sendInvoice()
                └─────┬─────┘
                      │  ksef_reference + element_reference zapisane
                      ▼
                ┌────────────────┐
                │ awaiting_upo   │  ← Invoice/Status polling co +30s/+60s
                └─────┬──────────┘
                      │  status=200 + Upo/{ref} fetched
                      ▼
                ┌────────────┐
                │ completed  │  ← UPO XML w storage/ksef/upo/{club_id}/{invoice_id}.xml
                └────────────┘     + club_invoices.status='accepted_ksef'

   Każdy step → exception → markRetry()
       attempts < 5  → retrying (next_retry_at = +backoff)
       attempts >= 5 → failed (wymaga force-retry przez admina)
```

## Retry policy

Exponential backoff: **1m, 5m, 30m, 2h, 12h**. Po 5 nieudanych próbach
status idzie na `failed` — wymaga ręcznej interwencji super admina
(`/admin/platform/ksef/queue` → "force-retry").

Stałe w kodzie: `KsefSendQueueModel::RETRY_DELAYS_SECONDS` +
`KsefSendQueueModel::MAX_ATTEMPTS`.

## Konfiguracja cron

Worker domyślnie pobiera **batch=10** z kolejki na uruchomienie. Zalecane
uruchamianie co minutę:

```cron
# /etc/cron.d/clubdesk-ksef
* * * * * www-data /opt/plesk/php/8.3/bin/php /var/www/clubdesk/cli/ksef_send_worker.php >> /var/log/clubdesk-ksef.log 2>&1
```

Plesk: użyj jawnej ścieżki do PHP 8.x — domyślny `/usr/bin/php` to często
PHP 7.4 i wymaganie `>= 8.1` z `bootstrap/php_version_check.php` zabije
worker. Bezpieczne PHP-binary:

- `/opt/plesk/php/8.3/bin/php`
- `/opt/plesk/php/8.2/bin/php`
- `/opt/plesk/php/8.1/bin/php`

### Wielu workerów równolegle

Worker używa `SELECT ... FOR UPDATE SKIP LOCKED` — bezpiecznie można odpalić
2-3 równoległe instancje na osobnych cronach lub w supervisord:

```
* * * * * /opt/plesk/php/8.3/bin/php /var/www/clubdesk/cli/ksef_send_worker.php 20
```

(argument = batch size, max 100).

## XAdES-BES — uwagi produkcyjne

`app/Helpers/Ksef/XAdESSigner.php` implementuje XAdES-BES enveloped
signature (RSA-SHA256 + sha256 digest, c14n 2001-03-15). Strict spec MF
może wymagać dodatkowych elementów (długoterminowe XAdES-T z timestamp z
TSA, OCSP responses), ale Phase 3 celuje w **MVP test-mode** — wystarczy
do `ksef-test.mf.gov.pl`.

Certyfikat: `.p12 / .pfx` musi siedzieć w `storage/ksef/{club_id}/` —
inne ścieżki są odrzucane przez `XAdESSigner::loadCertificate()` jako
path traversal. Hasło certyfikatu jest szyfrowane przez
`Encryption::encryptForClub()` (HKDF z `club_id` jako context — sekrety
jednego klubu nie da się odszyfrować kluczem drugiego).

## Troubleshooting

| Symptom | Przyczyna | Rozwiązanie |
|---|---|---|
| `XAdESSigner: cert_path nieprawidlowy` | Brak `.p12` w `storage/ksef/{club_id}/` | Wgraj certyfikat przez `/club/ksef-settings` |
| `XAdESSigner: nie udalo sie odczytac .p12 (zle haslo?)` | Nieprawidłowe hasło certyfikatu | Wpisz hasło ponownie w `/club/ksef-settings` |
| `KSeF API: invalidContextIdentifier` | NIP klubu nie jest zarejestrowany w KSeF lub źle wpisany | Zweryfikuj NIP w `/club/ksef-settings` i CEIDG/GUS |
| `KSeF server error (HTTP 500)` | Awaria po stronie MF | Retry automatyczny; sprawdź <https://www.podatki.gov.pl/ksef/komunikaty/> |
| Kolejka stoi w `awaiting_upo` przez >30 min | KSeF jeszcze nie wystawił UPO | Spokojnie — worker pollue co minutę, max 5 prób |
| `failed` po 5 próbach | Trwały błąd (cert wygasł, niepoprawny XML) | Sprawdź `last_error_message` w `/admin/platform/ksef/queue`, popraw i `force-retry` |

## Dokumentacja referencyjna

- KSeF API: <https://www.podatki.gov.pl/ksef/api-ksef/>
- Schema FA(2): <https://www.podatki.gov.pl/media/9264/schemat-fa2-1-0e-20231019.xsd>
- XAdES BES: <https://www.w3.org/TR/XAdES/>
- Środowisko testowe: <https://ksef-test.mf.gov.pl/>

## Bezpieczeństwo (CRITICAL)

- Wszystkie zapytania SQL z `WHERE club_id = ?` — multi-tenant izolacja.
- CSRF na każdym POST endpoincie (sprawdza `CsrfCoverageTest`).
- Hasło certyfikatu deszyfrowane tylko na czas `openssl_pkcs12_read()` i
  natychmiast zerowane (`str_repeat("\0", strlen($pwd))`).
- `ksef_session_token` jest czyszczony z `ksef_send_queue` po `completed`
  lub `failed` — nie zostaje na dysku po zakończeniu wysyłki.
- UPO XML zapisywane z perms 0640 (read/write owner, read group, brak www).
- Cert path *zawsze* w `storage/ksef/{club_id}/` — sanity check w
  `XAdESSigner::loadCertificate()` blokuje path traversal.
