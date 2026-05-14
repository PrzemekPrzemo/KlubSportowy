# Przewodnik dla zarządu klubu

Dla kogo: użytkownik z rolą **zarzad** w ClubDesk. Masz pełny dostęp do
wszystkich modułów klubu — od członków, przez finanse, po integracje
płatnościowe i federacyjne. Wszystko poza funkcjami platformowymi
(zarządzanie planami, inne kluby) — te są zarezerwowane dla Master
Admina platformy.

> Sprawy wspólne (logowanie, 2FA, dark mode, język, PWA) opisuje
> [Konto i podstawy](common.md). Zacznij od tamtego, jeśli to Twoje
> pierwsze logowanie.

---

## Pierwsze uruchomienie

Po założeniu klubu (przez Ciebie lub przez wsparcie ClubDesk) zobaczysz
**onboarding w 5 krokach** (`/onboarding/step1` … `step5`):

1. Dane klubu (nazwa, NIP, adres).
2. Wybór dyscyplin sportowych.
3. Pierwsze grupy treningowe i trenerzy.
4. Konfiguracja składek.
5. Zaproszenie pierwszych członków.

Możesz pominąć onboarding (`/onboarding/skip`) — wrócisz do konfiguracji
ręcznie. Po zakończeniu lądujesz na **Dashboardzie** (`/dashboard`)
z widżetami: zaległe składki, dzisiejsze treningi, nowi członkowie,
ogłoszenia. Układ widżetów zapisujesz przez **Personalizuj** —
preferencje są per-użytkownik.

[Zrzut: dashboard zarządu z widżetami]

---

## Dane klubu i ustawienia podstawowe

**Klub → Ustawienia** (`/club/settings`) — tutaj edytujesz:

- nazwa wyświetlana, NIP, REGON, KRS,
- adres siedziby, e-mail kontaktowy, telefon,
- strefa czasowa, waluta, format daty,
- ustawienia SMTP (`/club/smtp`) — własny serwer pocztowy dla
  powiadomień; alternatywnie używasz globalnego SMTP platformy.

Pole „opis klubu" wykorzystywany jest m.in. w stopce e-maili
i dokumentach PDF (zaświadczenia, umowy).

---

## Branding klubu

**Klub → Personalizacja** (`/club/customization`):

- **Logo klubu** — PNG/SVG, do 2 MB. Pokazuje się w sidebarze
  i w nagłówkach dokumentów.
- **Kolory** — kolor podstawowy (`--app-primary`) i tło paska bocznego
  (`--app-navbar-bg`). Wszystkie przyciski, badge i akcenty
  dziedziczą kolor podstawowy.
- **Favicon** — PNG/ICO, ładowany w **Personalizacja → Favicon**
  (`/club/customization/favicon`). Możesz go też usunąć
  (wraca wtedy systemowy).
- **Custom CSS** — pole tekstowe, przepuszczane przez sanitizer
  (`WhitelabelSanitizer::sanitizeCss`). Możesz dopasować typografię,
  spacing, ukryć elementy.
- **Header e-maila** — fragment HTML pojawiający się na górze
  każdego e-maila wychodzącego z systemu.
- **Komunikacja** — nadawca SMS (Sender ID), nadawca e-mail,
  motto klubu pod logiem.

[Zrzut: panel personalizacji z paletą kolorów i podglądem logo]

> Zmiany branding są widoczne natychmiast po zapisie — wystarczy
> odświeżyć stronę.

---

## Sporty klubu

**Sekcje sportowe** (`/sports`) — lista wszystkich dyscyplin
aktywowanych w Twoim klubie. ClubDesk obsługuje ponad 30 sportów
(piłka nożna, koszykówka, judo, BJJ, tenis, narciarstwo, łucznictwo,
brydż, golf, MMA, kolarstwo i wiele innych — pełna lista w
`/admin/sports/catalog` dla Master Admina).

Akcje:

- **Włącz sport** (`/sports/enable`) — dodaje sekcję do klubu.
- **Aktywuj** (`/sports/activate/:id`) — wybiera „aktywny sport" w
  bieżącym kontekście (filtruje listy zawodników, treningów).
- **Wyczyść aktywny** (`/sports/clear-active`) — przełącza na widok
  cross-sport.
- **Logo sportu** (`/sports/:id/logos`) — możesz wgrać własne logo
  dyscypliny (jasne i ciemne dla obu motywów).

W klubach multi-sport każdy zawodnik może być przypisany do wielu
sekcji jednocześnie — statystyki są agregowane w widoku
**Statystyki cross-sport** (poniżej).

---

## Zarządzanie użytkownikami klubu

**Klub → Użytkownicy** (`/club/users`) — lista pracowników klubu
(trenerzy, instruktorzy, sędziowie, księgowi, lekarze).

- **Dodaj użytkownika** (`/club/users/add`) — wpisz e-mail, wybierz
  rolę (`trener`, `instruktor`, `sedzia`, `lekarz`, `ksiegowy`,
  `zarzad`). Użytkownik dostanie e-mail z linkiem aktywacyjnym.
- **Odbierz dostęp** (`/club/users/:userId/revoke`) — konto pozostaje
  w bazie, ale traci dostęp do klubu. Możesz przywrócić w każdej
  chwili.
- **Uprawnienia per rola** (`/admin/clubs/:id/permissions`) —
  granularne włączanie/wyłączanie modułów dla każdej roli (RBAC).
  Reset przywraca domyślne uprawnienia.

> Sensitive role: `zarzad`, `trener`, `instruktor`, `lekarz` mają
> rozszerzony dostęp i są logowane przez audit (`AdminSensitiveAccess`).

---

## Konfiguracja składek

Sekcja **Finanse** w sidebarze obejmuje cały cykl składek:

1. **Stawki składek** (`/fees/rates`) — definiujesz typy
   (miesięczna, roczna, jednorazowa), kategorie wiekowe, sport,
   kwoty. Możesz włączać/wyłączać stawkę bez jej usuwania.
2. **Ulgi i zniżki** (`/fees/discounts`) — np. zniżka rodzinna,
   senior, junior. Stosowane przy generowaniu należności.
3. **Przydziały** (`/fees/assignments`) — wiążesz stawkę z
   konkretnym członkiem albo grupą. Akcja **Podgląd** liczy, ilu
   zawodników zostanie objętych przed zapisem.
4. **Należności** (`/fees/dues`) — wygenerowane składki do zapłacenia.
   Generujesz je masowo przyciskiem **Generuj należności**
   (`/fees/dues/generate`), z wyborem miesiąca i grupy.
5. **Akcje na należności**: oznacz jako zapłacone, anuluj, zwolnij
   z opłaty (`waive`).

[Zrzut: ekran generowania należności + podgląd kalkulacji]

---

## Bramki płatności

**Klub → Bramki płatności** (`/club/gateways`) — własne credentiale
dla każdego dostawcy:

- **Stripe** — `pk_live_…` + `sk_live_…` + webhook secret.
- **Przelewy24** — merchant ID, pos_id, klucz CRC.
- **PayU** — POS ID, drugi klucz (MD5).
- **Tpay** — merchant ID, kod bezpieczeństwa.

Każda bramka ma cykl: **Edytuj** (`:provider/edit`) → **Test
połączenia** (`:provider/test`) → **Włącz** (`:provider/toggle`) →
**Usuń** (`:provider/delete`).

> Test połączenia wysyła próbne wywołanie API dostawcy i potwierdza,
> że credentiale są poprawne. Zanim włączysz bramkę produkcyjnie,
> ZAWSZE wykonaj test.

Po włączeniu bramki członkowie widzą przycisk **Zapłać online** w
portalu (`/portal/payments`).

---

## Wysyłka InPost

**Klub → Wysyłka InPost** (`/club/shipping`):

1. **Edytuj konfigurację** (`/club/shipping/edit`) — wpisz token
   ShipX (z panelu InPost Manager) i `organization_id`.
2. **Test połączenia** (`/club/shipping/test`) — sprawdza, czy
   token działa.
3. **Włącz** (`/club/shipping/toggle`).
4. **Utwórz przesyłkę** (`/club/shipping/create`) — wybierz odbiorcę
   z listy członków, wpisz wymiary i wagę.
5. **Lista przesyłek** (`/club/shipping/shipments`) — historia ze
   statusami.
6. **Etykieta PDF** (`/club/shipping/label/:id`) — pobiera etykietę
   do druku.

---

## Federacje sportowe

**Klub → Federacje** (`/club/federations`) — eksport danych
zawodników do związków sportowych (PZPN, PZJ, PZTS itd. — zależnie
od dyscypliny).

1. Wybierz federację z listy, kliknij **Edytuj** (`:code/edit`).
2. Wpisz credentiale (login/API key — różne per federacja).
3. **Test połączenia** (`:code/test`).
4. **Włącz** (`:code/toggle`).
5. **Eksportuj zawodnika** (`:code/export-member`) — wybierz
   zawodnika z dropdownu i wyślij dane (imię, nazwisko, PESEL,
   data urodzenia, kategoria).

> Część federacji (np. PZSS) ma ograniczone wsparcie API — wtedy
> ClubDesk wyświetla badge „shotero.pl" sugerujące alternatywne
> rozwiązanie.

---

## Google Calendar

**Klub → Google Calendar** (`/club/google-calendar`) — dwukierunkowa
synchronizacja kalendarza klubu z Google Calendar:

1. **Połącz** (`/club/google-calendar/connect`) — przekierowanie
   do OAuth Google. Wybierz konto i zatwierdź uprawnienia.
2. **Konfiguracja** — wybierz kalendarz docelowy, kierunek
   synchronizacji (push / pull / bidirectional).
3. **Sync teraz** (`/club/google-calendar/sync-now`) — manualne
   wymuszenie synchronizacji.
4. **Rozłącz** (`/club/google-calendar/disconnect`) — usuwa token
   i przerywa synchronizację.

Synchronizowane są: treningi, mecze, turnieje, wydarzenia klubowe.

---

## Live updates

**Live updates** (`/live`) — strumień zdarzeń w czasie rzeczywistym
publikowany np. w trakcie meczu lub turnieju.

- **Kanały** (`/live/channels`) — lista publicznych kanałów klubu.
- **Utwórz kanał** (`/live/admin/create`) — nadaj nazwę i opis.
- **Start / End** (`/live/admin/start/:id`, `:id/end`) — sterowanie
  cyklem życia kanału.
- **Publikuj event** (`/live/publish/:channel`) — wysyła komunikat
  (gol, kara, zmiana, koniec rundy). Widoczny natychmiast u widzów.
- **Stream** (`/live/stream/:channel`) — endpoint Server-Sent Events
  dla podłączonych klientów.

Dodatkowo masz **Livestream** (`/livestream`) — wpisy YouTube/RTMP
do transmisji wideo z meczu.

---

## Szablony e-maili

**E-mail → Szablony** (`/email/templates`) — wszystkie powiadomienia
systemowe per zdarzenie:

- przypomnienie składki,
- potwierdzenie płatności,
- zaproszenie na trening,
- ogłoszenie klubowe,
- reset hasła,
- inne.

Klikasz typ powiadomienia (`/email/templates/:type`), edytujesz
treść (HTML + zmienne typu `{{member_name}}`, `{{amount}}`), zapisujesz
(`:type/save`). Dodatkowo definiujesz **Reguły powiadomień**
(`/club/notifications`) — kiedy i do kogo wysyłać.

---

## Webhooks

**Klub → Webhooks** (`/club/webhooks`) — wychodzące powiadomienia
HTTP dla integracji zewnętrznych (Zapier, Make, własny backend).

- **Dodaj webhook** (`/club/webhooks/create`) — URL docelowy +
  wybrane eventy (np. `member.created`, `payment.completed`,
  `training.attended`).
- **Usuń** (`/club/webhooks/:id/delete`).

Każdy webhook ma podpis HMAC w nagłówku — weryfikuj go po stronie
odbiorcy.

---

## Plan i rozliczenia

**Klub → Subskrypcja** (`/club/subscription`) — Twój aktualny plan,
limity (członkowie, miejsca dyskowe, dyscypliny), data odnowienia.

- **Dodatki** (`/club/subscription/addons`) — katalog rozszerzeń
  (np. dodatkowy sport, większa pojemność, wsparcie premium).
- **Kup dodatek** (`/club/subscription/addons/buy`).
- **Anuluj / wznów dodatek** (`:id/cancel`, `:id/reactivate`).

**Plan klubu** (`/billing/plans`) — zmiana planu głównego (upgrade).
**Faktury** (`/billing/invoices`) — historia rozliczeń, oznaczanie
faktur jako opłaconych (`:id/paid`).

---

## Dokumenty

**Dokumenty** (`/documents`) — generator PDF dla:

- **Umowa członkowska** (`/documents/agreement/:memberId`)
- **Zgoda na treningi** (`/documents/consent/:memberId`)
- **Oświadczenie o zwolnieniu z odpowiedzialności**
  (`/documents/waiver/:memberId`)
- **Zaświadczenie o członkostwie** (`/documents/membership/:memberId`)
- **Umowa członkostwa** (`/documents/contract/:memberId`)
- **Certyfikat osiągnięć** (`/documents/certificate/:memberId`)

Wszystkie dokumenty używają branding klubu (logo, kolory, motto).

Faktury wystawiasz w **Faktury klubu** (`/admin/invoices`) — wymaga
roli administratora platformy lub odpowiednich uprawnień.

---

## Backup i bezpieczeństwo

**Backup bazy** wykonywany jest centralnie przez wsparcie ClubDesk
(skrypt `cli/backup_club.php`). Nie masz bezpośredniego dostępu —
poproś `support@clubdesk.pl` o backup ad-hoc, jeśli go potrzebujesz.

**Eksport pełnych danych klubu** dostępny jest w `/club/export`
(JSON/ZIP). To Twoje prawo do przenoszenia danych (RODO art. 20).

**GDPR** (`/gdpr`) — zarządzanie zgodami, eksport danych
konkretnego członka, anonimizacja.

---

## Cross-sport stats

Jeśli Twój klub prowadzi więcej niż jedną dyscyplinę, w sidebarze pod
**Sporty** zobaczysz **Statystyki cross-sport**
(`/club/cross-sport-overview`):

- liczba aktywnych członków per dyscyplina,
- frekwencja na treningach,
- przychody per sekcja,
- top zawodnicy.

To widok zarządczy — pomaga decydować, które sekcje rozwijać.

---

## Q&A

**Jak dodać kolejnego administratora klubu?** **Klub → Użytkownicy →
Dodaj** z rolą `zarzad`. Nowy zarząd ma identyczne uprawnienia jak
Ty.

**Czy mogę zmienić dane klubu (NIP) po założeniu?** Tak — **Klub →
Ustawienia**. Tylko Master Admin platformy może zmienić
identyfikator klubu (slug) — wtedy zmieniają się URL-e.

**Co jeśli bramka płatności nie działa?** Wejdź w **Klub →
Bramki**, kliknij **Test połączenia**. Jeśli test się nie powiedzie
— sprawdź credentiale w panelu dostawcy. Webhook secret musi być
ustawiony zarówno w panelu Stripe/P24, jak i u nas.

**Jak zmienić limit członków?** Limit zależy od planu klubu —
zobacz **Subskrypcja** i kup dodatek lub wyższy plan. Master Admin
może też podnieść limit ad-hoc (`/admin/clubs/:id/limits`).

**Czy mogę całkowicie usunąć klub?** Tak, ale to nieodwracalne —
poproś wsparcie ClubDesk o usunięcie. Najpierw skorzystaj z
`/club/export`, żeby zachować dane.
