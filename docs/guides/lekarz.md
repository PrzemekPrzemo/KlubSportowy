# Przewodnik dla lekarza klubowego

Dla kogo: użytkownik z rolą **lekarz** w ClubDesk. Masz dostęp do
danych medycznych zawodników, prowadzisz badania okresowe, rejestrujesz
kontuzje i nadzorujesz zgodność anty-dopingową. Rola jest oznaczona
jako **sensitive** (zob. `Auth::SENSITIVE_ROLES`) — każdy Twój dostęp
do danych medycznych jest logowany.

> Funkcje wspólne (logowanie, 2FA, dark mode, język, PWA) opisuje
> [Konto i podstawy](common.md). Włącz 2FA — to wymaganie dla ról
> sensytywnych.

---

## Dashboard medyczny

`/dashboard` — widżety dopasowane do lekarza:

- **Badania wkrótce wygasające** — lista zawodników, którym kończy
  się ważność badań sportowych w najbliższych 30 dniach.
- **Otwarte kontuzje** — zawodnicy aktualnie w protokole return-to-play.
- **Powiadomienia** — wiadomości od trenerów (np. „zawodnik
  zgłosił ból"), zarządu.

[Zrzut: dashboard lekarza z ostrzeżeniami o wygasających badaniach]

---

## Karty medyczne zawodników

**Badania medyczne** (`/medical`) — wszystkie wpisy medyczne w klubie.

Lista zawiera:

- imię i nazwisko zawodnika,
- data badania,
- data ważności,
- wynik (zdolny / niezdolny / zdolny z ograniczeniami),
- typ badania (cardiology, ortopedyczne, ogólne).

### Profil medyczny zawodnika

Z listy klikasz w zawodnika lub przechodzisz przez **Członkowie →
profil → zakładka Medyczne** (`/members/:id`). Widzisz pełną historię
badań tego zawodnika.

> **WAŻNE: dostęp do tych danych jest logowany.** Każde otwarcie
> profilu medycznego trafia do audit logu
> (`AdminSensitiveAccessController`). Master Admin platformy widzi,
> kiedy i jakie rekordy zostały odczytane — to ochrona RODO i
> wymóg zgodności WADA.

---

## Wpisywanie badań okresowych

1. **Badania → Dodaj** (`/medical/create`).
2. Wypełnij formularz:
   - zawodnik (wybór z listy członków),
   - data badania,
   - typ badania (kardiologiczne, ortopedyczne, ogólne),
   - wynik (zdolny / niezdolny / zdolny z ograniczeniami),
   - data ważności,
   - notatki (komentarz wewnętrzny, opcjonalnie skan zaświadczenia).
3. **Zapisz** (`/medical/store`).

### Edycja / usunięcie

- **Edytuj** (`/medical/:id/edit`) — np. po otrzymaniu wyniku z
  laboratorium, dopisanie ostatecznej decyzji.
- **Usuń** (`/medical/:id/delete`) — używaj ostrożnie, lepiej
  oznaczyć jako nieaktualne. Usunięcie też trafia do audit logu.

Po wpisaniu wyniku „niezdolny" lub „zdolny z ograniczeniami"
trenerzy widzą badge ostrzegawczy w profilu zawodnika (bez
szczegółów medycznych — tylko status).

[Zrzut: formularz wpisywania badania okresowego]

---

## Kontuzje i return-to-play

Kontuzje rejestrujesz w profilu zawodnika (`/members/:id`). Pełny
moduł „Kontuzje" z protokołem return-to-play jest częścią pakietu
medycznego — sprawdź u zarządu, czy klub ma go aktywnego.

Typowy workflow:

1. Trener / instruktor zgłasza Ci kontuzję wiadomością
   (`/messages`).
2. Otwierasz profil zawodnika, dodajesz wpis medyczny typu
   „kontuzja" z opisem (lokalizacja, mechanizm, planowany czas
   rekonwalescencji).
3. Po okresie leczenia robisz badanie kontrolne — wpisujesz
   status „zdolny z ograniczeniami" lub „zdolny".
4. Trener widzi status w profilu zawodnika i wpuszcza go na
   treningi.

---

## Anti-doping (zgodność WADA)

**Zgodność WADA** (`/admin/compliance`) — moduł checklistowy dla
sportów, w których obowiązują przepisy World Anti-Doping Agency.

Funkcjonalności:

- lista zawodników objętych testami anty-dopingowymi,
- checklist procedur (informacja o lokalizacji, próbki, łańcuch
  dowodowy),
- raporty zgodności dla federacji.

Ten moduł jest dostępny tylko dla roli `lekarz` i `zarzad`
(uprawnienie `medical` + `sensitive`). Szczegóły procedur —
[wadq-ama.org](https://wada-ama.org).

Zawodnicy oznaczeni jako objęci anty-dopingiem widzą formularz
zgłaszania lokalizacji w portalu (`/portal/anti-doping`).

---

## Body metrics — pomiary cielesne

**Metryki zawodnika** (`/members/:id/metrics`) — pomiary
antropometryczne istotne medycznie (waga, wzrost, BMI, ciśnienie,
% tkanki tłuszczowej).

- **Dodaj pomiar** (`/members/:id/metrics/store`) — data i wartości.
- **Usuń pomiar** (`/members/:id/metrics/:mid/delete`).

Pomiary widzi też zawodnik w portalu (`/portal/body-metrics`) i może
dopisywać własne — Ty weryfikujesz i komentujesz.

---

## Eksport dokumentów medycznych do PDF

Standardowe dokumenty medyczne generujesz z **Dokumenty**
(`/documents`):

- **Zgoda na treningi** (`/documents/consent/:memberId`) — zawiera
  klauzulę zdrowotną.
- **Oświadczenie o zwolnieniu z odpowiedzialności**
  (`/documents/waiver/:memberId`).

Pełne karty medyczne z historią badań NIE są domyślnie eksportowane
jako PDF — to dane wrażliwe. Jeśli potrzebujesz wyeksportować
historię konkretnego zawodnika (np. zmiana lekarza klubowego, RODO
art. 20), poproś zarząd o eksport przez `/gdpr/member/:memberId/export`
— eksport obejmuje wszystkie dane członka, w tym medyczne.

---

## Konsultacje i kontakty awaryjne

W profilu zawodnika znajdziesz **Kontakty awaryjne**
(`/members/:id/emergency-contacts`) — telefon do opiekuna lub
najbliższej osoby. Używaj przy incydentach na treningu.

Możesz też dodawać i ustawiać główny kontakt
(`/members/:id/emergency-contacts/store`, `/.../:cid/primary`).

---

## Audit log — Twoje dostępy

Każde Twoje wejście w karty medyczne, edycja badania, eksport
dokumentu medycznego jest zapisywane w audit logu klubu. Logi widzi:

- Master Admin platformy (`/admin/audit/access-log`),
- zarząd Twojego klubu.

To nie ma na celu kontroli Twojej pracy — to **dowód zgodności**
z RODO i przepisami medycznymi (np. dla federacji wymagających
audytu dostępu do dokumentacji medycznej zawodników).

> Konsekwencja praktyczna: nie loguj się na konto innej osoby ani
> nie udostępniaj jej swojego — każdy dostęp jest przypisany do
> użytkownika i niezaprzeczalny.

---

## Q&A

**Nie widzę modułu Medyczne w sidebarze.** Sprawdź u zarządu —
moduł `medical` musi być włączony dla roli `lekarz` w
`/admin/clubs/:id/permissions`. Domyślnie tak jest, ale Twój klub
mógł to ograniczyć.

**Czy trener widzi szczegóły kontuzji?** Nie — trener widzi tylko
ogólny status „zdolny/niezdolny/z ograniczeniami". Szczegóły
medyczne (diagnoza, leki) są dostępne tylko dla roli `lekarz`.

**Zawodnik prosi o usunięcie swojej historii medycznej (RODO).**
Skieruj prośbę do zarządu — anonimizację wykonuje się przez
`/gdpr/member/:memberId/anonymize`. Z punktu widzenia regulacji
sportowych pełnego usunięcia historii nie wolno wykonać (federacja
może wymagać retencji X lat) — anonimizacja jest kompromisem.

**Jak udokumentować decyzję „niezdolny" — czy potrzebny jest
podpis?** Wpis w systemie jest zapisem klinicznym z timestampem
i Twoim ID — to wystarczy jako audit-able decyzja. Jeśli klub
wymaga papierowego dokumentu z pieczątką, dodaj skan w polu
„notatki".

**Pracuję w kilku klubach jako lekarz — czy widzę dane między
klubami?** Nie. Każdy klub jest izolowanym tenantem
(`AdminAudit/Isolation`). Dane medyczne zawodnika z klubu A NIE są
widoczne, gdy zalogujesz się do klubu B — nawet jeśli to ten sam
zawodnik.
