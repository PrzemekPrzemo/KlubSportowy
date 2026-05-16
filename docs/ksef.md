# KSeF — integracja z Krajowym Systemem e-Faktur

> **Phase 1 — foundation.** Aktualne wydanie zawiera tylko warstwę konfiguracji
> i test połączenia. Wystawianie i wysyłka faktur będą dostępne w Phase 2/3.

## Co to jest KSeF?

KSeF (Krajowy System e-Faktur) to ogólnopolska platforma Ministerstwa Finansów
do wystawiania, przesyłania i archiwizacji faktur elektronicznych w
strukturze XML zgodnej ze schemą FA(2)/FA(3). Od **2026** wystawianie
faktur ustrukturyzowanych w KSeF będzie obowiązkowe dla podatników VAT —
szczegóły zależą od aktualnego stanu prawnego (ustawa z 16.06.2023).

Linki:

- Strona MF: <https://www.podatki.gov.pl/ksef/>
- Dokumentacja API v1: <https://www.podatki.gov.pl/ksef/api-ksef/>
- API v2: <https://api.ksef.mf.gov.pl/docs/v2/>
- Środowisko testowe: <https://ksef-test.mf.gov.pl/>

## Jak skonfigurować KSeF w ClubDesk

### Krok 1 — Aktywacja przez administratora platformy

Integracja KSeF jest **wyłączona domyślnie** dla wszystkich klubów. Aby ją
włączyć:

1. Skontaktuj się z administratorem ClubDesk.
2. Administrator platformy aktywuje integrację dla Twojego klubu w panelu
   `/admin/platform/ksef`.
3. Po aktywacji w sidebarze pojawi się sekcja **KSeF — faktury**.

### Krok 2 — Uzyskanie tokena KSeF

Token autoryzacyjny generuje się raz w panelu MF:

1. Zaloguj się do <https://ksef-test.mf.gov.pl> (środowisko TEST) lub
   <https://ksef.mf.gov.pl> (PROD) Profilem Zaufanym, e-Dowodem lub kwalifikowanym certyfikatem.
2. Wybierz **Ustawienia → Tokeny KSeF → Wygeneruj nowy token**.
3. Nadaj tokenowi uprawnienia: `InvoiceWrite`, `InvoiceRead`, `CredentialsRead`.
4. **Skopiuj wygenerowany token** — wartość pokazywana jest tylko raz.

> Token jest ciągiem ok. 128 znaków. Traktuj go jak hasło — pozwala
> wystawiać i pobierać faktury w imieniu klubu.

### Krok 3 — Konfiguracja w ClubDesk

W `/club/ksef-settings`:

1. **NIP klubu** — 10 cyfr, bez kresek.
2. **Tryb** — wybierz `TEST` na start; `PROD` dopiero po pełnej walidacji.
3. **Token KSeF** — wklej w pole „Token autoryzacyjny".
4. **Certyfikat .p12** (opcjonalne) — wymagane do podpisu XAdES (Phase 3),
   dla samego MVP nie jest konieczne.
5. **Zapisz** — token zostanie zaszyfrowany AES-256-GCM (klucz wyprowadzany
   per klub przez HKDF-SHA256).

### Krok 4 — Test połączenia

W tej samej zakładce kliknij **Testuj połączenie**. System wywoła
`POST /online/Session/AuthorisationChallenge` i zweryfikuje, że:

- API KSeF jest dostępne,
- NIP klubu jest zarejestrowany w KSeF,
- ścieżka SSL klubu (TLS 1.2+) działa.

Wynik testu jest zapisywany w bazie i wyświetlany w panelu super admina.

## Bezpieczeństwo

- **Token KSeF** i **hasło do certyfikatu** są szyfrowane AES-256-GCM
  kluczem wyprowadzanym osobno dla każdego klubu (HKDF-SHA256, kontekst
  `clubdesk:club:{id}`). Dump bazy klubu A nie pozwala odszyfrować
  sekretów klubu B bez znajomości master key + ID ofiary.
- Certyfikat .p12 zapisywany jest w `storage/ksef/{club_id}/cert.p12`
  z uprawnieniami `0600`.
- Wszystkie zmiany konfiguracji są logowane do `ksef_audit_log`
  (user_id, IP, czas, opis — ale **nie** wartości sekretów).
- Każde wywołanie POST ma weryfikację CSRF.
- Dostęp do `/club/ksef-settings` mają tylko role `zarzad` i `admin` klubu.

## Co dalej (roadmap)

- **Phase 2** — tabela `club_invoices`, generator XML faktur zgodny ze schemą FA(2), UI listy faktur.
- **Phase 3** — XAdES signing certyfikatem kwalifikowanym + automatyczna wysyłka, kolejka błędów, retry.
- **Phase 4** — JPK_FA + automatyczny import sprzedaży zwrotnej (sales pull).

## FAQ

**Czy mogę używać KSeF bez certyfikatu kwalifikowanego?**
Tak — w trybie tokenowym (token KSeF). Certyfikat jest wymagany tylko dla
zaawansowanego wariantu XAdES, który zostanie wprowadzony w Phase 3.

**Czy NIP jest weryfikowany?**
Tak — formularz waliduje 10 cyfr + sumę kontrolną według algorytmu MF.

**Co się dzieje gdy token wygaśnie?**
Test połączenia zwróci błąd z kodem KSeF. Należy wygenerować nowy token w
panelu MF i podmienić go w `/club/ksef-settings` (puste pole = pozostaw
aktualny).
