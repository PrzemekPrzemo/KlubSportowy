# Przewodnik dla księgowego

Dla kogo: użytkownik z rolą **ksiegowy** w ClubDesk. Zarządzasz całym
cyklem finansowym klubu — składki, płatności, faktury, raporty,
prowizje trenerów. Domyślne uprawnienia roli (`database/schema.sql`)
to: członkowie (read), składki/finanse (write), raporty (write).

> Funkcje wspólne (logowanie, 2FA, dark mode, język, PWA) opisuje
> [Konto i podstawy](common.md).

---

## Dashboard finansowy

`/dashboard` — widżety dopasowane do księgowego:

- **Zaległe składki** — kwota i liczba członków z długiem.
- **Wpłaty miesiąca** — suma wpływów, porównanie z poprzednim
  miesiącem.
- **Faktury do wystawienia** — przypomnienia z systemu.
- **Powiadomienia** — m.in. nieudane transakcje z bramek.

Personalizacja widżetów: jak w innych rolach — przycisk
**Personalizuj** zapisuje układ per użytkownik.

[Zrzut: dashboard księgowego z głównymi wskaźnikami]

---

## Składki — definicje i stawki

**Stawki składek** (`/fees/rates`) — definicje opłat klubowych.

- **Lista** (`/fees/rates`) — wszystkie aktywne i nieaktywne stawki.
- **Edytuj** (`/fees/rates/:id/edit`) — kwota, częstotliwość
  (miesięczna/roczna/jednorazowa), kategoria wiekowa, sport.
- **Włącz/wyłącz** (`/fees/rates/:id/toggle`) — nie usuwa, ukrywa
  z generatora należności.
- **Usuń** (`/fees/rates/:id/delete`) — fizyczna usunie (uważaj —
  zniknie z historii kalkulacji).
- **Dodaj nową** (`/fees/rates/store`).

### Ulgi i zniżki

**Ulgi** (`/fees/discounts`) — np. zniżka rodzinna (drugie dziecko
50%), senior, junior, członek honorowy.

- **Dodaj ulgę** (`/fees/discounts/new`).
- **Edytuj** (`/fees/discounts/:id/edit`).
- **Włącz/wyłącz** (`/fees/discounts/:id/toggle`).

Ulgi stosowane są automatycznie przy generowaniu należności — system
sprawdza, czy zawodnik spełnia kryteria (wiek, status rodzinny).

---

## Przydziały składek

**Przydziały** (`/fees/assignments`) — wiążesz stawkę z konkretnym
członkiem lub grupą.

1. **Nowy przydział** (`/fees/assignments/new`) — wybierz stawkę,
   wybierz zawodników (pojedyncze lub całą grupę), data startu.
2. **Podgląd kalkulacji** (`/fees/assignments/preview`) — przed
   zapisem zobaczysz, ilu osób dotyczy i jaka będzie miesięczna
   suma.
3. **Edytuj / usuń** (`/fees/assignments/:id/edit`,
   `:id/delete`).

> Bez przydziału stawka „wisi" w systemie, ale nie generuje
> należności dla nikogo. Zawodnik bez przydzielonej stawki nie
> dostanie składki do zapłacenia.

---

## Płatności i należności

**Należności** (`/fees/dues`) — wygenerowane składki do zapłaty.

### Masowe generowanie

1. **Generuj należności** (`/fees/dues/generate`) — formularz z
   wyborem miesiąca, sportu, grupy. Generator tworzy należności na
   podstawie przydziałów i ulg.
2. **Odśwież kalkulację** (`/fees/dues/refresh`) — przelicza
   istniejące należności (np. po zmianie stawki).

### Akcje per należność

- **Oznacz jako opłaconą** (`/fees/dues/:id/pay`) — księguje
  wpłatę. Wybierasz datę i metodę (gotówka / przelew / bramka).
- **Zwolnij z opłaty** (`/fees/dues/:id/waive`) — np. dla
  zawodnika kontuzjowanego.
- **Anuluj** (`/fees/dues/:id/cancel`) — usuwa należność (rzadko
  używane).

### Płatność ręczna

**Dodaj wpłatę** (`/fees/new`, `/fees/store`) — gdy zawodnik
wpłacił poza systemem (gotówka, przelew bezpośredni). Wybierasz
członka, kwotę, datę, opis.

---

## Faktury

**Faktury klubu** (`/admin/invoices`):

- **Lista** (`/admin/invoices`) — wszystkie faktury platformy
  (subskrypcja ClubDesk).
- **Nowa faktura** (`/admin/invoices/create`) — formularz.
- **Faktura PDF** (`/admin/invoices/:id/pdf`) — generuje PDF z
  branding klubu.
- **Oznacz opłaconą** (`/admin/invoices/:id/pay`).
- **Anuluj** (`/admin/invoices/:id/cancel`).

Faktury wystawiane członkom (za składki) są w przygotowaniu —
obecnie do tego celu używaj **Raporty → Eksport CSV/PDF**.

---

## Zaległości — przypomnienia

**Należności** (`/fees/dues`) — filtruj listę po statusie „zaległe".

Wysyłanie przypomnień zaległości jest zautomatyzowane przez
**Reguły powiadomień** (`/club/notifications`) — zarząd ustawia
schemat (np. „przypomnienie po 7 dniach od terminu, eskalacja po
14 dniach").

Manualnie możesz wysłać przypomnienie zbiorczo:

1. **Członkowie** (`/members`) — przefiltruj „zaległe składki".
2. **Bulk action → Wyślij wiadomość** (`/members/bulk-message`).
3. Wybierz szablon (np. „Przypomnienie o składce") i wyślij.

---

## Raporty finansowe

**Raporty** (`/reports`):

- **Raport finansowy PDF** (`/reports/finances-pdf`) — pełny raport
  miesięczny/roczny: przychody, wydatki, zaległości, prowizje.
- **Raport finansowy CSV** (`/reports/finances-csv`) — to samo, ale
  w formacie do importu do księgowości zewnętrznej.
- **Raport miesięczny składek PDF** (`/reports/monthly-dues-pdf`) —
  zestawienie wpłat per członek za wybrany miesiąc.

Raporty zawierają branding klubu, mogą być wysłane do biura
rachunkowego.

[Zrzut: przykład raportu finansowego PDF]

---

## Księgowość — moduł rozliczeń

**Księgowość** (`/accounting`) — uproszczony dziennik wpłat i
wypłat klubu.

- **Lista wpisów** (`/accounting`) — chronologiczna księga z
  filtrami (data, kategoria, kwota).
- **Eksport CSV** (`/accounting/export`) — pełny eksport do
  Excela/programu księgowego.

---

## Eksport do CSV / XLSX

Większość list w ClubDesk ma przyciski eksportu:

- **Członkowie CSV** (`/reports/members-csv`).
- **Członkowie PDF** (`/reports/members-pdf`).
- **Finanse CSV / PDF** — patrz wyżej.
- **Księgowość CSV** — `/accounting/export`.

CSV używa kodowania UTF-8 z BOM (otwiera się poprawnie w Excelu po
polsku).

---

## Prowizje trenerów

**Prowizje trenerów** (`/club/trainers/commissions`) — system
rozliczeń wynagrodzeń.

- **Lista prowizji** (`/club/trainers/commissions`) — naliczone
  prowizje per trener, status (do wypłaty / wypłacone).
- **Raport** (`/club/trainers/commissions/report`) — zestawienie
  za okres.
- **Oznacz wypłacone** (`/club/trainers/commissions/mark-paid-out`)
  — po wykonaniu przelewu w banku.

### Stawki prowizji

**Stawki** (`/club/trainers/commissions/rates`):

- **Lista** — aktywne reguły naliczania (% od składek, stawka
  godzinowa, ryczałt).
- **Nowa stawka** (`/club/trainers/commissions/rates/new`).
- **Edytuj** (`/club/trainers/commissions/rates/:id/edit`).
- **Włącz/wyłącz** (`:id/toggle`).
- **Usuń** (`:id/delete`).

Trener widzi tylko swoje prowizje w `/trainer/commissions/my` — Ty
masz pełen wgląd we wszystkich.

---

## Plan klubu

**Subskrypcja klubu** (`/club/subscription`) — Twój aktualny plan,
limity, data odnowienia, dodatki.

Faktury za plan: `/billing/invoices`. Możesz oznaczyć fakturę jako
opłaconą (`/billing/invoices/:id/paid`) — przydatne, gdy płatność
nie została zarejestrowana automatycznie przez bramkę.

---

## Q&A

**Generator należności wygenerował duplikaty.** Sprawdź przydziały
(`/fees/assignments`) — prawdopodobnie ten sam członek ma dwa
nakładające się przydziały. Usuń jeden lub ustaw daty.

**Wpłata przyszła z bramki, ale należność nadal „zaległa".** Webhook
bramki mógł nie dotrzeć. Otwórz należność, zobacz log płatności,
ręcznie oznacz jako opłaconą (`/fees/dues/:id/pay`). Zgłoś sprawę
zarządowi — webhook secret w `/club/gateways` powinien być
sprawdzony.

**Czy mogę wystawić korektę faktury?** Obecnie korekty wystawiasz
przez **Anuluj** (`/admin/invoices/:id/cancel`) starej + utworzenie
nowej (`/admin/invoices/create`). Pełna funkcja korekt jest w
przygotowaniu.

**Eksport CSV ma „krzaki" w Excelu.** Excel czasem nie rozpoznaje
BOM. Otwórz CSV przez **Dane → Pobierz dane → Z pliku CSV** w Excelu
i wybierz kodowanie UTF-8 ręcznie.

**Jak rozliczyć trenera za pojedyncze zajęcia (zastępstwo)?**
Stwórz osobną stawkę prowizji typu „ryczałt"
(`/club/trainers/commissions/rates/new`) i przypisz ją do trenera
na ten miesiąc.
