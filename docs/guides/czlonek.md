# Przewodnik dla członka klubu (portal)

Dla kogo: zawodnik / członek klubu / rodzic zawodnika. Logujesz się do
**portalu członka** pod adresem `/portal/login` i masz dostęp do
swoich danych, składek, treningów, wyników, kalendarza, dokumentów.

> Funkcje wspólne (logowanie, 2FA, dark mode, język, PWA) opisuje
> [Konto i podstawy](common.md). Zacznij od tamtego, jeśli to Twoje
> pierwsze logowanie.

---

## Pierwsze logowanie

Dostajesz od klubu e-mail z linkiem zaproszenia. Po kliknięciu:

1. Ustaw silne hasło (min. 8 znaków).
2. Zaloguj się pod `/portal/login` — używasz swojego adresu e-mail
   i nowego hasła.
3. Po pierwszym logowaniu trafiasz na **Mój dashboard**
   (`/portal/dashboard`).

Jeśli zapomnisz hasła: `/portal/forgot-password` → wpisujesz e-mail
→ klikasz link z wiadomości → ustawiasz nowe hasło.

[Zrzut: ekran logowania portalu i zaproszenie e-mail]

---

## Mój dashboard

`/portal/dashboard` — Twój widok startowy z najważniejszymi danymi:

- **Najbliższe treningi** — kiedy i gdzie.
- **Status składki** — opłacona / zaległa / kolejna do zapłaty.
- **Ostatnie wyniki** — Twoje miejsca w turniejach / mecze.
- **Ogłoszenia klubu** — komunikaty od zarządu i trenera.
- **Powiadomienia** — nowe wiadomości, zaproszenia.

Jeśli klub jest multi-sport i trenujesz w kilku dyscyplinach,
dodatkowo masz **Cross-sport dashboard**
(`/portal/dashboard/cross-sport`) — zbiorcze statystyki ze wszystkich
sekcji.

---

## Mój profil

**Profil** (`/portal/profile`) — Twoje dane osobowe.

Możesz edytować (`/portal/profile/update`):

- imię i nazwisko (jeśli klub Ci na to pozwala),
- adres e-mail i telefon,
- adres korespondencyjny,
- awatar (`/portal/photo-upload`),
- dane opiekuna (gdy jesteś niepełnoletni).

**Karta członka** (`/portal/member-card`) — Twoja cyfrowa karta z
QR kodem. Pokazujesz ją na zawodach / wpuszczany jesteś na obiekt.

---

## Moje składki

**Składki** (`/portal/fees`) — przegląd Twoich aktywnych stawek
(jaką składkę płacisz, ile, w jakim cyklu).

**Należności** (`/portal/dues`) — konkretne kwoty do zapłaty:

- termin płatności,
- kwota,
- status (do zapłaty / opłacone / zaległe).

### Płatność online

Jeśli klub ma podpiętą bramkę płatności (Stripe / Przelewy24 / PayU /
Tpay), masz przycisk **Zapłać teraz**.

1. **Płatności** (`/portal/payments`) lub klikasz **Zapłać** przy
   konkretnej należności (`/portal/dues/:id/pay`).
2. Bramka przekierowuje Cię na stronę płatności.
3. Po zakończonej transakcji wracasz na `/portal/payments/success`
   z potwierdzeniem.
4. Status należności automatycznie zmienia się na „opłacone"
   (przez webhook bramki).

### Płatność offline

Jeśli wolisz przelew bezpośredni: skontaktuj się z księgowym
klubu. Po zaksięgowaniu wpłaty status zmieni się ręcznie.

---

## Mój kalendarz

**Harmonogram** (`/portal/schedule`) — Twoje treningi w widoku
tygodniowym/miesięcznym.

**Wydarzenia** (`/portal/events`) — mecze, turnieje, sparingi,
zgrupowania. Możesz potwierdzić obecność klikając w wydarzenie.

**Turnieje** (`/portal/tournaments`) — turnieje, do których możesz
się zarejestrować:

- **Zarejestruj się** (`/portal/tournaments/:id/register`).
- **Wycofaj zgłoszenie** (`/portal/tournaments/:id/withdraw`) —
  dopóki rejestracja jest otwarta.

**iCal subscribe** — w portalu klubu (`/calendar/ical`) dostępny
jest link subskrypcji kalendarza, który dodasz do Google Calendar /
Apple Calendar. Wtedy Twoje treningi pojawią się obok prywatnych
wydarzeń w telefonie.

---

## Moja obecność

**Obecność** (`/portal/attendance`) — historia Twoich obecności na
treningach. Widzisz:

- listę treningów,
- status (obecny / nieobecny / spóźniony),
- frekwencję w % (za miesiąc / za sezon).

To dane wpisane przez trenera/instruktora. Jeśli widzisz błąd
(byłeś, a jest „nieobecny") — wyślij wiadomość trenerowi
(`/portal/notifications`).

---

## Moje wyniki i rankingi

**Wyniki** (`/portal/results`) — Twoje wyniki w turniejach i meczach.
Format zależy od sportu:

- pozycja w turnieju (1./2./3.),
- punktacja techniczna,
- ranking ELO/krajowy,
- pasy/stopnie (BJJ, judo, karate).

**Stopnie/pasy** (`/portal/belts`) — historia awansów (dla sportów
z systemem stopni).

**Historia sportu** (`/portal/sport-history`) — kompletna chronologia
Twojej kariery w klubie, cross-sport jeśli trenujesz wiele
dyscyplin.

Dla każdego sportu jest dedykowany widok (`/portal/sport/:key`) —
np. `/portal/sport/bjj`, `/portal/sport/tennis`, `/portal/sport/swimming`.

---

## Moje dokumenty

Klub generuje za Ciebie standardowe dokumenty (są dostępne dla
Twoich rodziców/trenera, ale możesz je pobrać i Ty):

- **Umowa członkowska** — zwykle podpisywana raz przy zapisie.
- **Zgoda na treningi** — zawiera klauzulę zdrowotną.
- **Zaświadczenie o członkostwie** — np. do szkoły.
- **Certyfikaty osiągnięć** — pamiątkowe.

Aby pobrać dokument, skontaktuj się z zarządem klubu — generowanie
PDF z poziomu portalu członka jest w przygotowaniu.

---

## Moje uprawnienia sportowe i medyczne

**Licencje** (`/portal/licenses`) — Twoje aktywne licencje (np.
licencja zawodnicza PZPN, karta judo PZJ). Widzisz numer, datę
ważności.

**Badania medyczne** (`/portal/medical`) — Twoje aktualne badania:
data ostatniego, data wygaśnięcia, status. Lekarz klubowy wpisuje
wyniki — Ty masz tylko podgląd.

**Anti-doping** (`/portal/anti-doping`) — jeśli jesteś objęty
testami WADA, formularz zgłaszania lokalizacji (whereabouts) na
najbliższe 3 miesiące.

**Metryki cielesne** (`/portal/body-metrics`) — Twoja waga, wzrost,
BMI, % tkanki tłuszczowej. Możesz dopisywać własne pomiary lub
zostawić to lekarzowi.

**Dziennik treningów** (`/portal/training-log`) — Twój prywatny
dziennik. Wpisujesz, co robiłeś (intensywność, samopoczucie, czas).
Dane widoczne tylko dla Ciebie i trenera.

**Kontakty awaryjne** (`/portal/emergency-contacts`) — osoby do
kontaktu w razie wypadku (rodzic, partner). Możesz dodać kilka i
oznaczyć jedną jako główną.

---

## Powiadomienia

**Powiadomienia** (`/portal/notifications`) — Twoja lista
komunikatów: wiadomości od trenera, zmiany w planie treningów,
przypomnienia o składkach.

Klikasz powiadomienie → szczegóły. Automatycznie oznaczane jako
przeczytane (`/portal/notifications/:id/read`).

### Preferencje powiadomień

**Preferencje** (`/portal/notification-prefs`) — wybierasz kanały
dla każdego typu zdarzenia:

- e-mail,
- SMS (jeśli klub ma usługę SMS),
- push (aplikacja PWA).

Zapisujesz w `/portal/notification-prefs/update`. Wyłączenie jednego
kanału nie wyłącza pozostałych — to per-kanał i per-typ.

---

## Ustawienia konta

**Profil → Ustawienia**:

- **Zmiana hasła** (`/portal/password`) — wymaga starego hasła.
- **2FA** (`/portal/2fa/setup`) — patrz [Konto i podstawy](common.md#2fa-totp).
- **Kody zapasowe 2FA** (`/portal/2fa/backup-codes`).
- **Zgody RODO** (`/portal/consents`) — przegląd i edycja zgód
  (marketingowych, na publikację wizerunku, na przekazywanie danych
  do federacji).

---

## Wiele klubów / sekcji

Jeśli jesteś członkiem **kilku klubów** ClubDesk (np. trenujesz
w dwóch miejscach), po zalogowaniu wybierasz klub
(`/portal/club-select`). W każdym klubie widzisz osobne dane.

Jeśli klub ma **wiele sekcji sportowych** (multi-sport), przełączasz
sekcję przyciskiem **Przełącz sekcję** (`/portal/switch-section/:id`)
— wtedy dashboard i statystyki filtrują się do wybranej dyscypliny.

---

## Aplikacja mobilna (PWA)

Pełna instrukcja w [Konto i podstawy → PWA](common.md#instalacja-jako-aplikacja-mobilna-pwa).

W skrócie:

- **Android Chrome**: menu → Dodaj do ekranu głównego.
- **iPhone Safari**: Udostępnij → Dodaj do ekranu początkowego.
- Po instalacji włącz powiadomienia push (`/portal/push/subscribe`)
  — będziesz dostawał alerty nawet bez otwartej przeglądarki.

PWA działa częściowo offline — ostatnio załadowane treningi i
ogłoszenia widzisz bez internetu.

[Zrzut: ikona ClubDesk PWA na ekranie głównym telefonu]

---

## Q&A

**Nie dotarł e-mail z linkiem aktywacyjnym.** Sprawdź folder spam.
Jeśli nadal nic — poproś klub o ponowne wysłanie zaproszenia.
Trener/zarząd zrobi to w **Klub → Członkowie → profil zawodnika →
„Wyślij ponownie zaproszenie"**.

**Mam dwa konta — jak je połączyć?** Skontaktuj się z zarządem
klubu, oni mogą scalić konta (lub usunąć jedno z zachowaniem
historii). Z poziomu portalu nie zrobisz tego sam.

**Czy mogę zmienić swój e-mail?** Tak, w **Profil →
edycja** (`/portal/profile/update`). Po zmianie używasz nowego
e-maila do logowania.

**Nie widzę przycisku „Zapłać".** Twój klub nie ma podpiętej bramki
płatności online. Wpłać przelewem (numer konta podaje zarząd) i
poczekaj na zaksięgowanie ręczne przez księgowego.

**Mogę zarejestrować się w portalu bez zaproszenia z klubu?**
Domyślnie nie — konta w portalu tworzy klub. Jeśli Twój klub
włączył publiczną samorejestrację, znajdziesz link na stronie WWW
klubu lub w komunikacji marketingowej.
