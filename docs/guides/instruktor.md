# Przewodnik dla instruktora

Dla kogo: użytkownik z rolą **instruktor** w ClubDesk. Pomagasz w
prowadzeniu zajęć — zaznaczasz obecność, widzisz listę zawodników w
swojej grupie, komunikujesz się z nimi. Zakres uprawnień jest węższy
niż u trenera (np. nie zarządzasz wynikami turniejów, nie generujesz
składek).

> Funkcje wspólne (logowanie, 2FA, dark mode, język, PWA) opisuje
> [Konto i podstawy](common.md).

---

## Dashboard instruktora

`/dashboard` — widok startowy. Widzisz tu:

- **Dzisiejsze treningi** — lista przypisanych Ci zajęć.
- **Najbliższe wydarzenia** (mecze, sparingi, zgrupowania) — do
  podglądu.
- **Powiadomienia** — wiadomości od trenera i zarządu.
- **Skrót do listy moich zawodników**.

Personalizację widżetów ustawisz tym samym przyciskiem co inne role.

---

## Treningi przypisane do mnie

**Treningi** (`/trainings`) — lista wszystkich treningów w sekcji.
Jako instruktor zazwyczaj asystujesz przy zajęciach prowadzonych
przez trenera głównego — Twoje uprawnienia obejmują:

- **Podgląd treningu** (`/trainings/:id`) — szczegóły, lista
  uczestników.
- **Zaznaczanie obecności** (`/trainings/:id/attendance`) — patrz
  niżej.

Tworzenie i usuwanie treningów jest domyślnie zarezerwowane dla
trenera/zarządu. Jeśli klub przyznał Ci tę uprawnienie
(`/admin/clubs/:id/permissions`), zobaczysz też przycisk **Dodaj**
(`/trainings/create`).

---

## Zaznaczanie obecności

To najczęstsza akcja instruktora w aplikacji.

1. Otwórz trening (`/trainings/:id`) — lista wszystkich zawodników
   z Twojej grupy pojawi się automatycznie.
2. Per zawodnik wybierz status: **obecny**, **nieobecny**,
   **spóźniony**.
3. Kliknij **Zapisz obecność** — system wysyła POST do
   `/trainings/:id/attendance`.
4. Możesz wrócić i poprawić obecność po zakończeniu treningu
   (do końca tygodnia — później wymaga uprawnień zarządu).

Jeśli przyszedł ktoś gościnnie (np. zawodnik z innej grupy), dodajesz
go ręcznie przez **Dodaj uczestnika** (`/trainings/:id/attendee/add`).

[Zrzut: ekran obecności — lista zawodników z trzema buttonami per
osoba]

---

## Lista zawodników w grupie

**Członkowie** (`/members`) — lista zawodników. Jako instruktor masz
zazwyczaj dostęp w trybie **read-only**:

- przeglądasz dane podstawowe (imię, nazwisko, telefon kontaktowy
  rodzica),
- widzisz status medyczny (zdolny / niezdolny — bez szczegółów),
- nie edytujesz danych osobowych (to zarząd lub trener).

Profil zawodnika (`/members/:id`) pokazuje też:

- frekwencję w Twojej grupie,
- ostatnie wyniki/postępy,
- kontakty awaryjne (przydatne, gdy coś się stanie na treningu).

> Dane medyczne i finansowe są widoczne tylko dla odpowiednich ról
> (`lekarz`, `ksiegowy`). Jako instruktor ich nie zobaczysz.

---

## Komunikacja z zawodnikami

### Ogłoszenia

Możesz czytać ogłoszenia (`/announcements`), ale tworzenie zwykle
jest zarezerwowane dla trenera/zarządu. Sprawdź u administratora,
czy masz przyznane prawo `announcements.write`.

### Wiadomości

**Wiadomości** (`/messages`) — pełny dostęp:

- **Skrzynka** (`/messages`).
- **Wysłane** (`/messages/sent`).
- **Nowa wiadomość** (`/messages/compose`) — możesz pisać do
  zawodników z Twojej grupy i do innych pracowników klubu.

### Wiadomość zbiorcza

Jeśli zarząd nadał Ci uprawnienie, możesz wysłać wiadomość zbiorczą
do całej grupy (`/members/bulk-message`). W przeciwnym razie poproś
trenera.

---

## Moje prowizje

Jeśli klub używa systemu prowizji, w sidebarze pod „Finanse" zobaczysz
**Moje prowizje** (`/trainer/commissions/my`):

- naliczone prowizje (stawka godzinowa lub % od składek grupy),
- status (do wypłaty / wypłacone),
- historia z poprzednich miesięcy.

Nie masz dostępu do pełnej tabeli prowizji wszystkich trenerów — to
widzi tylko zarząd i księgowy.

---

## Sprzęt klubowy

Jeśli klub przyznał Ci dostęp do **Sprzęt** (`/equipment`), możesz:

- przeglądać dostępność sprzętu,
- wydać zawodnikowi (`/equipment/:id/assign`),
- przyjąć zwrot (`/equipment/:id/return/:aid`).

Dodawanie i usuwanie pozycji z inwentarza jest zwykle zarezerwowane
dla zarządu.

---

## Q&A

**Czym różnię się od trenera?** Trener prowadzi grupę pełnoetatowo
— tworzy treningi, edytuje skład, wystawia wyniki turniejów, dostaje
prowizję od członków grupy. Instruktor asystuje — zaznacza obecność
i prowadzi zajęcia, ale nie zarządza całością. Zakres uprawnień
ustala zarząd w `/admin/clubs/:id/permissions`.

**Nie widzę przycisku „Dodaj trening".** To uprawnienie zarządu.
Jeśli prowadzisz samodzielne zajęcia (np. zastępstwo), poproś
zarząd o nadanie `trainings.create`.

**Czy mogę zobaczyć dane medyczne zawodnika?** Nie — to dostępne
tylko dla roli `lekarz`. Widzisz jedynie ogólny status („zdolny do
treningu") jako badge w profilu.

**Mam problem ze zalogowaniem.** Sprawdź, czy używasz `/auth/login`
(nie `/portal/login` — to dla zawodników). Reset hasła znajdziesz w
[Konto i podstawy](common.md#reset-hasla).

**Czy mogę edytować obecność z poprzedniego tygodnia?** Tak, dopóki
nie minął okres edycji (zwykle 7 dni). Po tym czasie poproś zarząd
o korektę.
