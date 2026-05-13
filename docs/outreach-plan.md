# ClubDesk — Plan outreach (pierwsza fala 50–100 klubów)

Dokument odpowiada na zadanie Todoist „Wybrać i przygotować listę klientów
(klubów sportowych) do wysyłki outreach".

## Cel

Pierwsza fala: **50–100 klubów sportowych** w PL, kontakt 1:1, test
message-market fit przed skalowaniem.

Sukces fali: ≥ 5% conversion na demo, ≥ 1% conversion na płatnego klienta
(czyli 0,5–1 klient z pierwszej fali). Jeśli niżej — pivot komunikatu albo ICP
przed kolejną falą.

## Ideal Customer Profile (ICP)

**MUST have:**
- Klub w PL (na razie wyłącznie polski rynek — bramki, federacje, język)
- Wielkość: **50–500 członków** (mniejsze nie zapłacą, większe mają już systemy)
- Sport jeden z naszych 6 sportów z pełnym MVP (Football, Basketball, Volleyball,
  Shooting, Rollerskating, Athletics) — żeby nie obiecywać czego nie mamy
- Aktywny profil (FB lub IG lub strona z wydarzeniami < 30 dni)
- Brak zaawansowanego systemu IT (sygnał: zapisy przez formularz Google, kasa
  przez prywatne konto bankowe, kalendarze w Excelu)

**Nice to have:**
- Wiele sekcji sportowych (nasza unique selling proposition: multi-sport
  jednym narzędziem)
- Zarząd w wieku 30–50 (technologicznie obyty, decyzje szybsze)
- Sport sponsorowany lub z grantami (mają budżet)

**Wykluczenia:**
- Kluby <50 członków (nieopłacalne dla obu stron)
- Wielkie kluby ekstraklasy (mają custom soft, dużo polityki)
- Sporty, których jeszcze nie domknęliśmy MVP (uzupełniamy zgodnie z ROADMAP.md
  P1 #3)

## Źródła leadów

| Źródło | Liczność | Jakość | Koszt | Compliance |
|---|---|---|---|---|
| Publiczne bazy federacji (PZPN, PZKosz, PZPS itd.) | ~3000+ klubów łącznie | Wysoka — aktualne | 0 | RODO art. 6 ust. 1 lit. f (uzasadniony interes) — udokumentować |
| Krajowy Rejestr Stowarzyszeń (KRS / REGON) | ~10k+ | Średnia (brak email, są adresy) | 0 | Publiczne |
| LinkedIn (prezesi/zarządy klubów) | wąsko, ale jakościowo | Wysoka | 0 (Sales Navigator: ~150 PLN/mc) | OK przy 1:1 |
| Google search per region („klub <sport> <miasto>") | duża | Średnia | 0 | OK |
| Facebook strony klubów | duża | Średnia (kontakt przez Messenger) | 0 | OK |
| Networking / polecenia | mała, ale konwersja najwyższa | Bardzo wysoka | 0 | OK |

**Strategia mix:** zacząć od **federacji + LinkedIn** (jakość), uzupełniać
Google (skala) w kolejnych falach.

## Format target list (Google Sheet / Airtable)

Kolumny:

| Kolumna | Typ | Notatki |
|---|---|---|
| `club_id` | numer | wewn. ID |
| `club_name` | text | |
| `sport_primary` | enum | jeden z 6 MVP |
| `sport_secondary` | enum[] | dla multi-sport USP |
| `region` | enum | województwo |
| `city` | text | |
| `est_members` | range | <50 / 50–150 / 150–500 / >500 |
| `website` | url | |
| `email` | email | |
| `phone` | text | |
| `contact_person` | text | imię + funkcja |
| `contact_source` | text | „strona klubu" / „LinkedIn" / „polecenie X" |
| `data_source_legal_basis` | text | „rejestr publiczny PZPN" / „strona www" |
| `current_system` | text | „Excel" / „nic" / „inny SaaS: nazwa" |
| `status` | enum | new / contacted / replied / demo / customer / lost |
| `first_touchpoint_date` | date | |
| `last_touchpoint_date` | date | |
| `notes` | text | |
| `assigned_to` | text | kto prowadzi |

Plik startowy: `docs/outreach/target-list.csv` (do utworzenia po pierwszym
przebiegu zbierania).

## Compliance — RODO

Dla każdego rekordu **musi być udokumentowane źródło**:
- Rejestr publiczny → podstawa: art. 6 ust. 1 lit. f (prawnie uzasadniony interes:
  marketing B2B w branży)
- Strona www klubu z opublikowanym kontaktem → j.w.

W pierwszym mailu/kontakcie:
- Krótka informacja o źródle danych
- Link do polityki prywatności ClubDesk (czeka na finalizację template'ów —
  Todoist notatka strategiczna)
- Opcja sprzeciwu/usunięcia w jednym kliknięciu

## Outreach playbook — pierwszy kontakt

**Kanał 1 (preferowany): email + LinkedIn touchpoint**

Sekwencja:
1. Dzień 0: email z personalizacją (imię prezesa, sport, jeden konkret z ich klubu)
2. Dzień +3: LinkedIn connect (bez sprzedaży, tylko „śledzę wasz klub")
3. Dzień +7: follow-up mail z wartością (case study lub artykuł, nie sprzedaż)
4. Dzień +14: break-up email („zamykam temat, daj znać jeśli zainteresowani")

**Kanał 2 (uzupełnienie): telefon** — tylko jeśli email nie odpowiedział + jest publiczny numer.

## Template'y maili (do dopracowania)

**Mail 1 — pierwszy kontakt (szablon):**

```
Temat: [Klub <nazwa>] — szybkie pytanie o zarządzanie sekcjami

Cześć <imię>,

Widzę, że <Klub X> prowadzi <sport> i jednocześnie <sport2> — to dość rzadkie,
że klub utrzymuje aż <N> sekcji równolegle. Sam zarząd tym, czy też jest osobny
zespół?

Pytam, bo budujemy narzędzie (ClubDesk) dla klubów multi-sport — jedna baza
członków, składki, treningi i wyniki dla wszystkich sekcji. Większość systemów
wymusza po jednym kliencie per sport, co dla was byłoby koszmarem.

Jeśli temat ma sens — chętnie pokażę 15 min demo dopasowane do waszej struktury.
Jeśli nie — bez problemu, daj znać i nie zawracam głowy.

Pozdrawiam,
<imię>
```

**Mail 2 (follow-up) i Mail 3 (break-up):** do napisania po pierwszych odpowiedziach
z fali 1 — chcemy reagować na realne pytania, nie zgadywać.

## Mierzenie

Tygodniowy review:
- Liczba wysłanych / odpowiedzi / demo umówione / closed
- Powody „no" — taksonomia (cena / nie teraz / mają inny / nie rozumieją wartości)
- Komunikat który najlepiej konwertuje → wzmocnić w kolejnej fali

## Kolejne fale (po MMF check)

- **Fala 2 (po 4 tyg.):** +200 klubów; jeśli message działa, skalowanie
- **Fala 3 (po 8 tyg.):** rozszerzenie ICP o pozostałe sporty po ich MVP-zacji
  (zgodnie z ROADMAP.md P1 #3)
- **Fala 4:** wejście w sporty, gdzie federacje są kluczowe — po wdrożeniu
  exportów (ROADMAP.md P1 #4)
