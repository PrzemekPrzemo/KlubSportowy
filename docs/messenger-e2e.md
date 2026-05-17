# Messenger E2E — szyfrowanie end-to-end (opt-in)

## TL;DR

- Wiadomosci w komunikatorze klubowym mozna szyfrowac end-to-end (E2E).
- Klucz jest wywodzony z **passphrase znanej tylko czlonkowi** w przegladarce.
- **Server nigdy nie widzi plaintextu** i nie moze zdeszyfrowac tresci.
- Admin klubu rowniez **nie ma wgladu** — to wymog modelu (feature, nie bug).
- Funkcja jest **opcjonalna i per-watek** — pozostale rozmowy dzialaja jak dotad.

## Co E2E chroni

- Poufnosc tresci wobec serwera (operatora platformy ClubDesk).
- Poufnosc tresci wobec administratora klubu, jezeli ten ma dostep do bazy.
- Poufnosc tresci wobec napastnika ktory wykradnie dump bazy SQL.

## Czego E2E **nie** chroni

- **Metadanych**: kto z kim rozmawial, kiedy, jak duzo (audit log, statystyki).
- **Push notifications**: pokazuja jedynie placeholder "Zaszyfrowana wiadomosc".
- **Backupow przegladarki**: jezeli OS/przegladarka backupuje IndexedDB.
- **Atakow XSS**: jezeli atakujacy wstrzyknie skrypt w UI portalu, ma dostep do
  klucza w pamieci przegladarki. CSP + CSRF chronia best-effort.

## Architektura kryptograficzna

```
passphrase (user input, >= 8 znakow)
    |
    | PBKDF2-SHA256 (150_000 iter)
    | salt = "messenger-e2e-v1|<member_id>"
    v
master key (256-bit, ONLY in memory)
    |
    | HMAC-SHA256 (key=master, info="thread:<id>")
    v
per-thread AES-256 key + fingerprint (16B SHA-256 prefix)

encrypt: AES-GCM-256, random 12-byte IV
ciphertext stored as base64(payload+tag), iv stored as base64
```

### Server-side schema

`chat_messages`:
- `is_encrypted` (TINYINT) — flaga
- `encryption_version` ("AES-GCM-256-v1") — wersjonowanie
- `ciphertext_meta` (JSON) — `{iv, alg, key_fingerprint}`
- `body` — base64(ciphertext) gdy `is_encrypted=1`

`message_threads`:
- `e2e_enabled` (TINYINT) — czy watek wymaga szyfrowania
- `e2e_key_fingerprint` (CHAR(32)) — kanoniczny fingerprint do walidacji

`messenger_member_keys`:
- `passphrase_hash` — Argon2id (server-side hash hex klienta z PBKDF2)
- `recovery_phrase_encrypted` — opcjonalna fraza, szyfrowana per-klub

## Flow uzytkownika

1. **Setup** (jednorazowo): `Portal -> Wiadomosci -> Wlacz szyfrowanie E2E`.
   Czlonek wybiera passphrase (min. 8 znakow) + opcjonalnie fraze odzyskiwania.
2. **Wlacz dla watku**: w naglowku rozmowy "Wlacz szyfrowanie".
   Pierwsza wlaczona przez kogokolwiek z uczestnikow ustala stan watku.
3. **Wysylanie**: przegladarka szyfruje plaintext przed POST.
4. **Odczyt**: po wejsciu w watek modal "Wpisz passphrase" odblokuje sesje.
   Klucz zyje tylko w pamieci karty — refresh wymaga ponownego wpisania.

## Trade-offs i ograniczenia (uczciwie)

- **Zapomnienie passphrase = utrata historii** (jezeli nie ustawiono recovery).
- **Per-member, nie per-device**: ta sama passphrase na kazdym urzadzeniu.
- **Recovery phrase** jest szyfrowana per-klub kluczem serwera — wiec operator
  PLATFORMY (z dostepem do master key) teoretycznie moglby odzyskac. Akceptowalne
  dla "lite" E2E; dla pelnej hermetycznosci nie uzywaj recovery.
- **Forward secrecy = brak**. Symmetric key zyje az do zmiany passphrase.
- **Brak group ratchet** (np. Double Ratchet / MLS). MVP.

## Operacje admina

- **Wglad w tresc**: niemozliwy. W audit log widoczne sa tylko metadane.
- **Push notifications**: tytul i preview sa zastapione placeholderem.
- **Eksport danych GDPR**: zaszyfrowane wiadomosci eksportowane sa **jako
  ciphertext** — uzytkownik musi je sam zdeszyfrowac swoja passphrase.

## Bezpieczenstwo implementacji

- CSRF na wszystkich POST.
- Rate-limit `messenger_e2e_setup`: 3 proby / 60 min / member.
- Walidacja whitelisty algorytmow: tylko `AES-GCM-256`.
- Walidacja rozmiaru ciphertext: max 8 KB / wiadomosc.
- Server hashuje `password_hash` (Argon2id) klienta PBKDF2 — chroni przed
  replay raw hash.
- Klucze zyja **wylacznie w pamieci** karty — refresh = lock.

## Manualny smoke test

1. Zaloguj dwoch czlonkow (A i B) w dwoch oknach incognito.
2. Oba ustawiaja passphrase (kazdy swoja).
3. A wlacza E2E dla watku z B.
4. A wysyla "Hello E2E". B widzi `[zaszyfrowana wiadomosc]` az do wpisania passphrase.
5. Po unlock obu, oba widza plaintext.
6. W bazie: `SELECT body, is_encrypted, ciphertext_meta FROM chat_messages
   WHERE thread_id = ? ORDER BY id DESC LIMIT 5` — body to base64, nigdy plaintext.

## TODO (post-MVP)

- Double Ratchet / forward secrecy.
- Per-device keys (multi-device sync).
- Verifiable group membership (MLS).
- Wlasne klucze attachmentow (na razie zalaczniki sa **niezaszyfrowane**).
