# Przewodnik dla trenera

Dla kogo: użytkownik z rolą **trener** w ClubDesk. Prowadzisz grupę
treningową lub kilka grup — masz dostęp do zawodników, treningów,
wydarzeń, statystyk, komunikacji oraz sprzętu klubowego.

> Funkcje wspólne (logowanie, 2FA, dark mode, język, PWA) opisuje
> [Konto i podstawy](common.md).

---

## Dashboard trenera

`/dashboard` — Twój punkt startowy. Widzisz tu widżety:

- **Dzisiejsze treningi** — lista zaplanowanych na dziś, z linkiem
  do listy obecności.
- **Najbliższe wydarzenia** — mecze, turnieje, sparingi.
- **Powiadomienia** — zaproszenia, zmiany w planie, wiadomości od
  zarządu.
- **Skrót do moich grup** — szybkie przejście do listy zawodników.

Kolejność widżetów ustawisz w **Personalizuj dashboard** — wybór
zapamiętuje się per użytkownik.

[Zrzut: dashboard trenera z widżetami]

---

## Treningi

**Treningi** (`/trainings`) — lista wszystkich treningów w sekcji.

### Tworzenie treningu

1. **Treningi → Dodaj** (`/trainings/create`).
2. Wypełnij: data, godzina, miejsce, grupa/sekcja sportu, opis.
3. Zapisz — pojawi się na liście oraz w kalendarzu klubu.

### Edycja i anulowanie

- **Edytuj** (`/trainings/:id/edit`) — zmiana terminu, miejsca,
  opisu.
- **Usuń** (`/trainings/:id/delete`) — fizycznie usuwa trening.
  Zawodnicy dostaną powiadomienie e-mail (jeśli reguła „trening
  anulowany" jest aktywna).

### Lista obecności

Otwórz trening (`/trainings/:id`), zobaczysz pełną listę zawodników
przypisanych do grupy z trzema akcjami per osoba: **obecny /
nieobecny / spóźniony**. Klikasz **Zapisz obecność**
(`/trainings/:id/attendance`) — frekwencja zostaje zapisana i
liczy się do statystyk.

Możesz też ręcznie dodawać zawodników do treningu
(`/trainings/:id/attendee/add`) lub usuwać
(`/trainings/:id/attendee/:attendeeId/remove`) — przydatne, gdy
ktoś przyszedł gościnnie.

---

## Zawodnicy w grupie

**Członkowie** (`/members`) — lista zawodników klubu. Filtruj po
grupie, sekcji sportowej i statusie (aktywny / zawieszony /
archiwalny).

- **Dodaj członka** (`/members/create`) — pełny formularz (imię,
  nazwisko, data ur., kontakt, opiekun).
- **Edytuj** (`/members/:id/edit`).
- **Profil zawodnika** (`/members/:id`) — dane, kontakty awaryjne,
  metryka cielesna (`/members/:id/metrics`), badania, statystyki.
- **Zaznacz nieaktywnego** — w bulk action z poziomu listy
  (`/members/bulk`).

Możesz wysłać wiadomość zbiorczą do wybranych zawodników
(`/members/bulk-message`, `bulk-message/send`) — patrz Komunikacja
niżej.

---

## Statystyki zawodników

**Statystyki zawodnika** (`/stats/member/:memberId`) — profil z:

- frekwencją na treningach (% obecności),
- udziałem w meczach i turniejach,
- wynikami (sport-specific — np. ringi w łucznictwie, czasy w
  pływaniu, tabele BJJ).

**Porównaj zawodników** (`/stats/compare`) — wybierz 2–4 osoby
i zobacz ich statystyki obok siebie. Przydatne przed selekcją
składu.

[Zrzut: profil statystyk zawodnika z wykresem frekwencji]

---

## Wydarzenia

**Wydarzenia** (`/events`) — mecze, turnieje, sparingi, zgrupowania.

1. **Dodaj wydarzenie** (`/events/create`) — typ, data, miejsce,
   przeciwnik (opcjonalnie), sport.
2. **Lista** (`/events`) — filtrowana po dacie i sporcie.
3. **Usuń** (`/events/:id/delete`).

Dla turniejów drabinkowych użyj dedykowanego modułu **Turnieje**
(`/tournaments`):

- **Utwórz turniej** (`/tournaments/create`).
- **Dodaj uczestnika** (`/tournaments/:id/participant`).
- **Generuj drabinkę** (`/tournaments/:id/generate`) — automatyczne
  rozstawienie (single/double elimination, round-robin — zależnie
  od sportu).
- **Wpisz wynik meczu** (`/tournaments/match/:matchId/result`).

---

## Wyniki

Wyniki meczów i zawodów wpisujesz na dwa sposoby:

- **Z poziomu turnieju** (drabinka) — patrz wyżej.
- **Foto-wynik** (`/results`) — zrób zdjęcie tablicy/protokołu, prześlij
  (`/results/upload`), system rozpoznaje wynik i pyta o potwierdzenie
  (`/results/:id/save`). Przydatne na wyjazdach, gdy nie masz czasu
  ręcznie wpisywać.
- **Rankingi sportu** (`/sport-rankings`) — wpisujesz pozycję
  zawodnika w rankingu (np. WT dla taekwondo, WBC dla boksu).
  Akcja **Przelicz ranking** (`/rankings/recalculate`) odświeża
  pozycje na podstawie wszystkich wyników.

---

## Komunikacja z zawodnikami

### Ogłoszenia klubowe

**Ogłoszenia** (`/announcements`) — komunikaty widoczne w portalu
członka i na dashboardzie.

- **Dodaj** (`/announcements/create`) — tytuł, treść, opcjonalne
  zdjęcie/załącznik.
- **Edytuj** (`/announcements/:id/edit`).
- **Usuń** (`/announcements/:id/delete`).

Ogłoszenia trafiają do **wszystkich członków klubu** chyba że
zaznaczysz konkretną grupę/sekcję.

### Wiadomości prywatne

**Wiadomości** (`/messages`) — skrzynka indywidualna.

- **Skrzynka** (`/messages`) — odebrane.
- **Wysłane** (`/messages/sent`).
- **Nowa wiadomość** (`/messages/compose`) — wybierz adresata
  (zawodnik / inny pracownik klubu), temat, treść.
- **Wątek** (`/messages/:id`) — pełna konwersacja.

### Wysyłka zbiorcza

**Członkowie → Bulk message** (`/members/bulk-message`) — zaznacz
kilku zawodników i wyślij jednym strzałem (e-mail / SMS / push —
zależnie od preferencji odbiorcy).

---

## Sprzęt klubowy

**Sprzęt** (`/equipment`) — inwentarz klubu (worki bokserskie, piłki,
narty, kije, raki itd.).

- **Lista** (`/equipment`) — co jest w dyspozycji, co wydane.
- **Dodaj sprzęt** (`/equipment/store`) — nazwa, kategoria, numer
  inwentarzowy.
- **Wydaj zawodnikowi** (`/equipment/:id/assign`) — wybierz osobę,
  data wydania.
- **Odbierz zwrot** (`/equipment/:id/return/:aid`).
- **Karta sprzętu** (`/equipment/:id`) — historia wydań.

---

## Moje prowizje

Jeśli klub używa systemu prowizji trenerskich, masz dostęp do
**Moje prowizje** (`/trainer/commissions/my`) — widoczne w grupie
„Finanse" sidebara.

Widzisz tu:

- prowizje naliczone (po stawce % od składek lub kwocie ryczałtowej),
- status (do wypłaty / wypłacone),
- historię wypłat z poprzednich miesięcy.

Zarząd zarządza całą tabelą w **Prowizje trenerów**
(`/club/trainers/commissions`) — Ty widzisz tylko swoje.

---

## Cross-sport overview

Jeśli prowadzisz zajęcia w wielu sekcjach (np. piłka nożna + tenis),
zobaczysz **Statystyki cross-sport** (`/club/cross-sport-overview`)
w sidebarze — agregaty z wszystkich Twoich grup.

---

## Q&A

**Czy mogę usunąć zawodnika z klubu?** Możesz usunąć członka tylko
jeśli zarząd dał Ci tę uprawnienie (`/admin/clubs/:id/permissions`).
Domyślnie trener może edytować i przypisywać, ale nie usuwać.

**Zawodnik nie pojawia się w mojej grupie.** Sprawdź **Profil
zawodnika → Grupy** — upewnij się, że jest przypisany do Twojej
grupy/sekcji. Jeśli nie, dodaj go (lub poproś zarząd).

**Nie widzę modułu Treningi w sidebarze.** Moduł `trainings` musi
być włączony przez zarząd dla Twojej roli (`/admin/clubs/:id/permissions`
→ rola `trener` → moduł `trainings` → włącz).

**Czy mogę edytować dane medyczne zawodnika?** Nie — to zarezerwowane
dla roli `lekarz`. Widzisz tylko ogólny status („zdolny/niezdolny do
treningu") na karcie zawodnika.

**Jak wysłać przypomnienie o treningu?** Reguły powiadomień są
ustawiane przez zarząd (`/club/notifications`). Możesz też ręcznie
wysłać wiadomość zbiorczą (bulk-message).
