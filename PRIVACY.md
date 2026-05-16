# Polityka Prywatności i Prawa RODO — ClubDesk

Niniejszy dokument opisuje, w jaki sposób ClubDesk przetwarza dane osobowe członków klubów oraz w jaki sposób członkowie mogą realizować swoje prawa wynikające z RODO (Rozporządzenie 2016/679).

## Dane przetwarzane przez system

System przetwarza następujące kategorie danych:

- **Dane identyfikacyjne**: imię, nazwisko, PESEL, data urodzenia
- **Dane kontaktowe**: e-mail, telefon, adres (szyfrowane AES-256-GCM)
- **Dane sportowe**: frekwencja, wyniki, udział w wydarzeniach, rankingi, licencje
- **Dane medyczne**: badania lekarskie, orzeczenia zdolności (wrażliwa kategoria)
- **Dane finansowe**: płatności składek, faktury
- **Dane techniczne**: adres IP, user agent (do celów audytu)

## Prawa członka

### Art. 15 — Prawo dostępu

Członek ma dostęp do wszystkich swoich danych w portalu (`/portal`).

### Art. 16 — Prawo do sprostowania

Członek może aktualizować swoje dane w portalu (`/portal/profile`).

### Art. 17 — Prawo do bycia zapomnianym (usunięcie konta)

Członek może zgłosić wniosek o anonimizację swoich danych:

- URL: `/portal/gdpr/delete-account`
- Wymaga potwierdzenia e-mailem (link ważny 24 h).
- Po potwierdzeniu dane PII są nullifikowane (anonimizacja zachowuje rekord
  z `is_anonymized=1` dla spójności agregatów klubu).
- Operacja jest **nieodwracalna**.

### Art. 20 — Prawo do przenoszenia danych (eksport ZIP)

Członek może zażądać eksportu wszystkich swoich danych w formacie ZIP (JSON +
PDF + zdjęcia). Funkcja **dostępna jest w portalu**:

- **URL**: `/portal/gdpr/export`
- **Realizacja**: do 24 h od potwierdzenia wniosku (cron `cli/process_gdpr_exports.php`
  uruchamiany co 5 minut).
- **Format**: ZIP zawierający:
  - `data/profile.json`, `payments.json`, `trainings.json`, `tournaments.json`,
    `events.json`, `medical.json`, `consents.json`, `communications.json`,
    `achievements.json`, `rankings.json`, `licenses.json`, `body_metrics.json`,
    `notification_prefs.json`, `gdpr_requests.json`
  - `documents/*.pdf` — umowy, zaświadczenia, licencje
  - `photos/*.jpg` — zdjęcie profilowe
  - `manifest.json` — spis plików + SHA-256 checksumy + metadane
  - `README.txt` — opis zawartości po polsku
- **Format dat**: ISO 8601 (`YYYY-MM-DD HH:MM:SS`).
- **Format JSON**: UTF-8, pretty-print.
- **Ważność linku do pobrania**: 7 dni od wygenerowania.
- **Cleanup**: cron `cli/gdpr_cleanup_exports.php` (codziennie o 3:00) automatycznie
  usuwa wygasłe pliki.

Pełna instrukcja techniczna: zobacz [`docs/gdpr.md`](docs/gdpr.md).

### Art. 18 / 21 — Ograniczenie i sprzeciw

Pozostałe wnioski (`rectify`, `restrict_processing`, `object`) wymagają
ręcznego rozpatrzenia przez administratora klubu w `/admin/gdpr`.

## Bezpieczeństwo danych

- **Szyfrowanie at-rest**: PESEL, e-mail, telefon szyfrowane AES-256-GCM
  (klucz: `config/encryption.local.php`). Per-club HKDF derivation dla
  separacji kryptograficznej między klubami.
- **Multi-tenant isolation**: każdy SELECT/UPDATE filtrowany `WHERE club_id = ?`
  (defense in depth — niezależnie od warstwy aplikacyjnej).
- **Audit log**: każda operacja GDPR rejestrowana w `tenant_access_log`
  (severity `info` dla read, `critical` dla delete).
- **Pliki eksportu**: poza webrootem (`storage/gdpr_exports/{club_id}/{request_id}.zip`),
  chmod 0600, serwowane przez kontroler z weryfikacją `member_id`.
- **Hasła**: bcrypt (`portal_password`); tokeny GDPR confirmation: 64 znaki hex,
  ważne 24 h.

## Inspektor Ochrony Danych

Każdy klub działa jako niezależny administrator danych. ClubDesk
(operator platformy) jest podmiotem przetwarzającym (procesor).

Wnioski RODO kieruj do administracji swojego klubu lub na adres IOD klubu.

## Wersja

Ostatnia aktualizacja: 2026-05-16. Migracja wprowadzająca eksport ZIP: `077_gdpr_requests.sql`.
