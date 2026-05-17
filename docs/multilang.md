# Multilang (PL / EN) — architektura i workflow

ClubDesk obsługuje dwa języki interfejsu: polski (PL) i angielski (EN).
Obsługa obejmuje portal członka, panel admina, e-maile transakcyjne
oraz wybrane generatory PDF.

## Wspierane locale

```
['pl', 'en']
```

Stała: `App\Helpers\Translator::SUPPORTED`. Hard fallback: `'pl'`
(stała `Translator::FALLBACK`). Każda wartość spoza whitelisty
jest sprowadzana do `'pl'` (defense-in-depth na poziomie helper-a).

## Cascade — jak rozstrzygamy locale dla requesta

`Translator::setLocaleForUser(?int $userId, ?int $memberId, ?int $clubId)`:

1. `members.preferred_locale` (jeśli `memberId` jest podany i kolumna istnieje)
2. `users.preferred_locale` (jeśli `userId` jest podany; kolumna best-effort)
3. `Session::get('locale')` — np. po explicit `?lang=pl|en`
4. `clubs.default_locale` (jeśli `clubId` podany)
5. `Accept-Language` (header HTTP — `en*` → en, else pl)
6. Hard fallback `'pl'`

Każda warstwa jest *best-effort*: brak migracji, brak rekordu lub błąd
DB powodują przejście do następnej warstwy. Dzięki temu kod jest
bezpieczny do uruchomienia nawet przed wykonaniem migracji 098.

Cascade działa automatycznie w `public/index.php` po starcie sesji:

```php
\App\Helpers\Translator::setLocaleForUser($adminUserId, $portalMemberId, $portalClubId);
```

Explicit `?lang=pl|en` w URL zawsze wygrywa i zapisuje wybór w sesji.

## Bulk send / per-recipient locale

Do wysyłki e-maila lub generowania PDF dla pojedynczego odbiorcy
z innym locale niż request, używamy `withLocale`:

```php
foreach ($recipients as $r) {
    Translator::withLocale($r['preferred_locale'] ?? 'pl', function () use ($r) {
        EmailService::queueFromTemplate(
            $clubId,
            'fee_reminder',
            $r['email'],
            ['recipient_member_id' => $r['id'], /* ... */],
            $r['name'],
            $r['preferred_locale'] ?? null,   // <-- explicit locale param
        );
    });
}
```

`withLocale` jest exception-safe: locale jest przywracane nawet jeśli
callback rzuci wyjątek.

## Dodawanie nowych tłumaczeń

Klucze są w `lang/pl/messages.php` i `lang/en/messages.php`. Obie tablice
muszą mieć **dokładnie te same klucze** (parity test:
`tests/Unit/EmailTemplateLocaleTest::test_pl_and_en_message_files_have_parity`).

Format klucza: dotted namespace, np. `portal.profile.locale.title`,
`club.settings.default_locale.help`. Param interpolacja przez `:name`:

```php
'portal.dash.days_short' => ':days dni',
// uzycie:
__('portal.dash.days_short', ['days' => 14]);  // -> "14 dni"
```

## Dodawanie nowego locale (np. UK, DE)

W przyszłości można rozszerzyć obsługę o kolejny język:

1. Dodaj `'de'` do `Translator::SUPPORTED`.
2. Stwórz `lang/de/messages.php` z 1:1 kluczami z `pl`.
3. Dodaj wartości w `email_event_catalog_translations` (`locale='de'`)
   dla istotnych templates.
4. Zaktualizuj selektor języka w `app/Views/layouts/{main,portal}.php`
   i UI radio buttons (portal profile, club settings, wizard step1).
5. Dodaj DE pod sekcję `portal.profile.locale.*` w obu istniejących `messages.php`.
6. Migracja: nie wymagana (kolumny `CHAR(2)` już obsługują).

## E-mail templates per-locale

Tabela: `email_event_catalog_translations`

```sql
CREATE TABLE email_event_catalog_translations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  locale CHAR(2) NOT NULL,
  subject VARCHAR(500) NOT NULL,
  body TEXT NOT NULL,
  UNIQUE KEY uniq_event_locale (event_id, locale),
  FOREIGN KEY (event_id) REFERENCES email_event_catalog(id) ON DELETE CASCADE
);
```

Lookup w `EmailService::queueFromTemplate()`:

1. Resolve effective locale (param → member → club → 'pl').
2. Próba `WHERE locale = effective`.
3. Jeśli brak → fallback `WHERE locale = 'pl'` (avoid send-fail).
4. Jeśli nadal brak → użyj `default_subject` / `default_body`
   z `email_event_catalog` (per-club override z `email_templates`).

Aby dodać nowe EN tłumaczenie:

```sql
INSERT INTO email_event_catalog_translations (event_id, locale, subject, body)
SELECT id, 'en', 'Welcome to {{club.name}}', 'Hi {{member.first_name}}, ...'
FROM email_event_catalog WHERE code = 'member_welcome';
```

## PDF — respect locale

PDF generatory akceptują optional `?string $locale = null`. Wewnątrz
opakowują render w `Translator::withLocale($locale, ...)`:

```php
InvoicePdf::generate($data, $buyer['preferred_locale'] ?? null);
BeltCertificatePdf::generate($belt, $member, $beltMap, $sport, $fed,
    $member['preferred_locale'] ?? null);
```

Aktualne PDF (Invoice, BeltCertificate) mają jeszcze inline PL stringi —
docelowo zamieniamy je na `__()` aby `withLocale` faktycznie zmieniał
treść. Wrapper jest gotowy.

## Migracja DB

`database/migrations/098_multilang_preferences.sql`:
- `members.preferred_locale` CHAR(2) NULL
- `clubs.default_locale` CHAR(2) NOT NULL DEFAULT 'pl'
- `email_event_catalog_translations` (event_id, locale, subject, body)
- Seed: 'pl' z istniejących `default_*`, 'en' dla 12 kluczowych eventów
