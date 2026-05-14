# Przewodnik dla sędziego

Dla kogo: użytkownik z rolą **sedzia** w ClubDesk. Wpisujesz wyniki
meczów i turniejów, zarządzasz rozgrywkami, publikujesz oficjalne
protokoły. Domyślne uprawnienia roli to **wydarzenia** (`events`,
write) oraz **kalendarz** (`calendar`, read).

> Funkcje wspólne (logowanie, 2FA, dark mode, język, PWA) opisuje
> [Konto i podstawy](common.md).

---

## Dashboard sędziego

`/dashboard` — wita Cię widok z:

- **Wydarzenia do osądzenia** — mecze/turnieje czekające na wynik.
- **Kalendarz na ten tydzień** — kiedy i gdzie sędziujesz.
- **Powiadomienia** — wiadomości od zarządu, zaproszenia.

Personalizujesz widżety jak każda inna rola.

---

## Wydarzenia oczekujące na wyniki

**Wydarzenia** (`/events`) — pełna lista wydarzeń klubu (mecze,
turnieje, sparingi).

Filtruj listę po:

- dacie (dzisiaj / ten tydzień / przyszłe),
- statusie (zaplanowane / w trakcie / zakończone),
- sporcie.

Dla zakończonych wydarzeń bez wpisanego wyniku kliknij **Wpisz
wynik** — formularz wyniku jest dopasowany do dyscypliny.

[Zrzut: lista wydarzeń z badge „brak wyniku" przy zakończonych]

---

## Wpisywanie wyników meczu / turnieju

### Drabinka turnieju (rekomendowane)

**Turnieje** (`/tournaments`) — moduł do drabinek eliminacyjnych.

1. Otwórz turniej (`/tournaments/:id`).
2. Klikasz mecz w drabince — pojawia się formularz wyniku.
3. Wpisujesz wynik (POST do `/tournaments/match/:matchId/result`).
4. System automatycznie przesuwa zwycięzcę w drabince i przelicza
   ranking.

Formularz wyniku jest **sport-specific** — np.:

- Piłka nożna: wynik X:Y, strzelcy, kartki.
- BJJ: punktacja techniczna, submission, czas.
- Boks/kickboxing: rundy, KO/TKO/decyzja, sędziowska karta.
- Łucznictwo: punkty per zawodnik.
- Tenis: sety i gemy.
- Brydż: kontrakt, lewy.

Pełna lista obsługiwanych formularzy: `app/Views/_partials/sport_result_*.php`
(każda dyscyplina ma swój widok).

### Wynik wydarzenia (bez drabinki)

Dla meczu poza turniejem otwierasz wydarzenie z **Wydarzenia** i
wpisujesz wynik w analogicznym formularzu.

### Foto-protokół

Jeśli prowadziłeś mecz w terenie i nie miałeś czasu klikać —
**Wyniki ze zdjęcia** (`/results`):

1. **Prześlij zdjęcie protokołu** (`/results/upload`,
   `storeUpload`).
2. System próbuje rozpoznać wynik (OCR).
3. Otwierasz wynik (`/results/:id`), korygujesz dane jeśli trzeba,
   klikasz **Zapisz** (`/results/:id/save`).
4. Możesz usunąć zdjęcie po zatwierdzeniu (`/results/:id/delete`).

---

## Tworzenie eventów

Jeśli klub przyznał Ci uprawnienie `events.write` (domyślnie tak —
patrz `database/schema.sql`):

1. **Wydarzenia → Dodaj** (`/events/create`).
2. Wypełnij: typ (mecz/turniej/sparing), data, miejsce, sport,
   przeciwnik (opcjonalnie).
3. Zapisz (`/events/store`) — pojawia się na liście i w kalendarzu.

Usuwanie wydarzenia (`/events/:id/delete`) — używaj ostrożnie,
zwłaszcza gdy są już zarejestrowani zawodnicy.

---

## Sprawdzanie rankingów

**Rankingi sportu** (`/sport-rankings`) — aktualna pozycja zawodników
w rankingach (klubowych i krajowych).

- **Lista** (`/sport-rankings`) — wszystkie aktywne rankingi.
- **Dodaj wpis** (`/sport-rankings/store`) — ręczne wpisanie pozycji
  (gdy import z federacji nie działa).
- **Przelicz** (`/rankings/recalculate`) — odświeża rankingi na
  podstawie wszystkich wyników w bazie (uwzględnia ELO, punkty FIDE
  itd. w zależności od dyscypliny).

Rankingi są publiczne — widać je też w portalu członka
(`/portal/results`).

---

## Ogłoszenia

Jeśli masz przyznane prawo `announcements.write`, możesz publikować
**Ogłoszenia** (`/announcements`):

- **Dodaj** (`/announcements/create`) — np. „Wyniki turnieju
  X opublikowane".
- **Edytuj** (`/announcements/:id/edit`).
- **Usuń** (`/announcements/:id/delete`).

Domyślnie publikacja ogłoszeń jest zarezerwowana dla trenerów i
zarządu — sprawdź u administratora klubu.

---

## Eksport wyników do PDF

**Raporty** (`/reports`):

- **Protokół wydarzenia** (`/reports/event-protocol/:id`) —
  oficjalny PDF z wynikiem meczu/turnieju, podpisy, branding klubu.

Plik możesz przesłać do federacji, opublikować na stronie klubu lub
zachować w archiwum.

---

## Live updates

Jeśli zarząd dał Ci dostęp, możesz publikować updates podczas meczu:

1. **Live → Kanały** (`/live/channels`) — lista aktywnych kanałów.
2. **Publikuj event** (`/live/publish/:channel`) — wysyła krótki
   komunikat (gol, kartka, koniec rundy) na żywo do widzów
   podglądających mecz online.

Tworzenie kanałów (`/live/admin/create`) i sterowanie cyklem życia
(start/end) jest zarezerwowane dla zarządu.

---

## Q&A

**Wpisałem zły wynik — jak poprawić?** Otwórz mecz w drabince
(`/tournaments/:id`) i kliknij wynik ponownie. Możesz nadpisać,
dopóki turniej nie został zamknięty. Po zamknięciu — poproś zarząd
o korektę.

**Nie widzę modułu Turnieje w sidebarze.** Uprawnienie `events.write`
musi być włączone dla roli `sedzia` (`/admin/clubs/:id/permissions`).
Domyślnie tak jest, ale Twój klub mógł to zmienić.

**Czy mogę sędziować w wielu klubach?** Tak — jeden adres e-mail
może być przypisany do wielu klubów (zarząd musi Cię zaprosić
osobno w każdym). Po zalogowaniu wybierasz klub w `/club-select`.

**Foto-protokół nie rozpoznał wyniku poprawnie.** OCR ma ograniczoną
dokładność — zawsze sprawdź wynik przed kliknięciem **Zapisz** i
popraw ręcznie. Sugestia: rób zdjęcie protokołu w dobrym świetle,
prostopadle, bez cieni.

**Jak zobaczyć moje statystyki sędziowskie?** Statystyki sędziów to
funkcja w przygotowaniu — obecnie liczbę osądzonych meczów widzisz
filtrując **Wydarzenia** po Twoim ID.
