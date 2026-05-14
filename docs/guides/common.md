# Konto i podstawy obsługi

Dla kogo: każdy użytkownik ClubDesk — niezależnie od roli (zarząd, trener,
instruktor, sędzia, księgowy, lekarz, członek-portal). Ten dokument
opisuje funkcje wspólne: logowanie, 2FA, ustawienia osobiste, dark mode
i instalację aplikacji jako PWA.

---

## Logowanie i bezpieczeństwo konta

ClubDesk ma dwa osobne loginy:

- **Panel klubu** (zarząd, trenerzy, instruktorzy, sędziowie, księgowi,
  lekarze) — adres `/auth/login`.
- **Portal członka** — adres `/portal/login`. Tu logują się zawodnicy,
  rodzice i członkowie klubu.

Do logowania używasz e-maila (lub loginu, jeśli zarząd Ci taki nadał)
oraz hasła ustawionego w e-mailu zapraszającym lub przy aktywacji konta.

> Wskazówka: po trzech nieudanych próbach logowania Twoje IP zostaje
> tymczasowo zablokowane. Jeśli widzisz „Zbyt wiele prób” — zaczekaj
> kilka minut lub poproś administratora klubu o odblokowanie.

[Zrzut: ekran logowania panelu i portalu obok siebie]

---

## 2FA (TOTP)

ClubDesk obsługuje dwuskładnikowe uwierzytelnianie zgodne ze standardem
TOTP (RFC 6238). Działa z każdą popularną aplikacją: Google
Authenticator, Microsoft Authenticator, Authy, Bitwarden, 1Password.

### Włączenie 2FA (panel klubu)

1. Otwórz **Konto → 2FA** (link w sidebar: ikona kłódki).
2. Zeskanuj wyświetlony kod QR aplikacją mobilną.
3. Wpisz 6-cyfrowy kod z aplikacji w polu „Potwierdź".
4. Zapisz kody zapasowe w bezpiecznym miejscu (np. menedżer haseł).

### Włączenie 2FA (portal członka)

1. **Profil → 2FA → Skonfiguruj** (`/portal/2fa/setup`).
2. Kroki identyczne jak wyżej.
3. Kody zapasowe pobierzesz w **Profil → Kody zapasowe**
   (`/portal/2fa/backup-codes`). Możesz wygenerować nowe — stare
   przestaną wtedy działać.

Po włączeniu 2FA każde logowanie wymaga 6-cyfrowego kodu (lub kodu
zapasowego, gdy nie masz dostępu do telefonu).

---

## Reset hasła

Jeśli zapomniałeś hasła:

1. Na ekranie logowania kliknij **Nie pamiętam hasła**.
2. Podaj adres e-mail przypisany do konta.
3. Sprawdź skrzynkę — link do resetu hasła jest ważny 60 minut.
4. Ustaw nowe hasło (min. 8 znaków, zalecane 12+).

Portal członka ma osobną ścieżkę: `/portal/forgot-password`.

---

## Ustawienia osobiste

W sidebarze pod nagłówkiem **Konto** widzisz swoje imię i nazwisko.
Z tego miejsca masz dostęp do:

- **2FA** — konfiguracja dwuskładnikowego logowania (patrz wyżej).
- **Dark mode** — przełącznik ciemnego motywu.
- **Język** — PL/EN.
- **Pomoc** — to centrum pomocy, w którym czytasz ten przewodnik.
- **Wyloguj**.

W portalu członka (`/portal/profile`) dodatkowo edytujesz dane
kontaktowe, awatar, zgody marketingowe oraz preferencje powiadomień
(e-mail / SMS / push) w `/portal/notification-prefs`.

---

## Dark mode

Przełącznik **Dark mode** znajduje się w sidebar pod sekcją „Konto".
Kliknięcie zmienia motyw natychmiast i zapamiętuje wybór w pamięci
przeglądarki (localStorage). Tryb ciemny obejmuje cały panel klubu
oraz portal członka.

Jeśli używasz wielu urządzeń, motyw należy włączyć osobno na każdym
z nich — nie jest synchronizowany przez konto.

---

## Język interfejsu (PL / EN)

W sidebar znajdziesz miniaturowy przełącznik **PL | EN** (ikona
„translate"). Kliknięcie języka przeładowuje stronę z nowym tłumaczeniem
— wybór zapisuje się w sesji oraz w preferencjach konta. Możesz też
wymusić język przez parametr `?lang=pl` lub `?lang=en` w URL.

Polski jest podstawowy i pokryty w 100%. Angielski to wersja
przewodnia (część etykiet wraca do polskiego, jeśli nie ma jeszcze
tłumaczenia).

---

## Pomoc i wsparcie

- **Centrum pomocy** (`/help`) — ten zbiór dokumentów.
- **Wsparcie wewnętrzne** (`/support`) — formularz zgłoszenia widoczny
  dla zalogowanych użytkowników klubu. Zgłoszenie trafia do Master
  Admina platformy.
- **E-mail awaryjny** — `support@clubdesk.pl`.

Przy zgłaszaniu błędu podaj zawsze: rolę użytkownika, ID klubu (widoczne
w adresie URL po wybraniu klubu), datę i godzinę zdarzenia, treść
komunikatu błędu (jeśli się pojawił).

---

## Instalacja jako aplikacja mobilna (PWA)

ClubDesk to PWA — możesz „zainstalować" go na telefonie tak, jak
zwykłą aplikację. Działa offline (cachowane dane), pokazuje
powiadomienia push i ma własną ikonę na pulpicie.

### Android (Chrome)

1. Otwórz ClubDesk w Chrome — najlepiej zaloguj się przed instalacją.
2. Menu (trzy kropki) → **Dodaj do ekranu głównego** lub
   **Zainstaluj aplikację**.
3. Potwierdź — ikona pojawi się obok innych aplikacji.

### iPhone / iPad (Safari)

1. Otwórz ClubDesk w Safari (nie Chrome — Apple wymusza Safari dla PWA).
2. Przycisk **Udostępnij** (kwadrat ze strzałką) → **Dodaj do ekranu
   początkowego**.
3. Zatwierdź — ikona ClubDesk pojawi się na ekranie.

### Powiadomienia push

W aplikacji zalogowanej jako członek klubu wejdź w **Profil →
Powiadomienia** i włącz „Powiadomienia push". Przeglądarka zapyta o
zgodę — kliknij **Zezwól**. Powiadomienia działają nawet, gdy
aplikacja jest zamknięta.

> iOS wspiera PWA push od wersji 16.4 — wcześniejsze systemy nie
> dostaną powiadomień push (pozostają e-mail/SMS).

---

## Q&A

**Czy mogę używać tego samego loginu w panelu klubu i w portalu
członka?** Nie. To dwie osobne bazy — Twój adres e-mail może być
przypisany jednocześnie do konta pracownika klubu (panel) i konta
zawodnika (portal), ale są to dwa osobne loginy.

**Zgubiłem telefon z 2FA.** Skorzystaj z kodu zapasowego (z listy
wygenerowanej przy konfiguracji). Jeśli i te zgubiłeś — zarząd
klubu może wyłączyć Ci 2FA z poziomu **Klub → Użytkownicy**.

**Czy aplikacja działa offline?** Częściowo. Po zainstalowaniu jako
PWA portal członka wyświetla ostatnio załadowane dane (treningi,
ogłoszenia, członek-card). Zapisywanie nowych rekordów wymaga
połączenia.

**Gdzie zmienić e-mail kontaktowy?** Portal członka:
`/portal/profile`. Panel klubu (trener, sędzia itd.): poproś zarząd
o aktualizację w **Klub → Użytkownicy**.
