# ClubDesk — Przewodnik użytkownika

Dokument przeznaczony dla zarządu klubu sportowego oraz osób zarządzających sekcjami.

---

## 1. Rejestracja

1. Wejdź na stronę główną aplikacji i kliknij **Zarejestruj klub**.
2. Wypełnij formularz: nazwa klubu, e-mail administratora, hasło.
3. Na podany adres e-mail zostanie wysłany link aktywacyjny — kliknij go w ciągu 24 h.
4. Po aktywacji zostaniesz przekierowany do panelu konfiguracji klubu.

> **Uwaga:** Jeden adres e-mail może być powiązany z wieloma klubami — wystarczy zmienić kontekst klubu w menu górnym.

---

## 2. Konfiguracja klubu

- **Dane podstawowe** — nazwa, adres, NIP, REGON, telefon kontaktowy.
- **Logo i kolory** — prześlij logo (PNG/SVG, max 2 MB) oraz wybierz kolory brandingowe.
- **Strefa czasowa** — domyślnie `Europe/Warsaw`.
- **Moduły** — włącz lub wyłącz poszczególne moduły (składki, wydarzenia, badania lekarskie itp.).
- **Role i uprawnienia** — zdefiniuj role (np. trener, sekretarz, skarbnik) i przypisz im dostęp do modułów.

Zmiany konfiguracji są zapisywane natychmiast. Niektóre opcje (np. zmiana planu subskrypcyjnego) mogą wymagać potwierdzenia e-mailem.

---

## 3. Sekcje sportowe

Każdy klub może prowadzić wiele sekcji sportowych (np. piłka nożna, siatkówka, judo).

1. Przejdź do **Ustawienia → Sekcje sportowe**.
2. Kliknij **Dodaj sekcję** i wybierz dyscyplinę z katalogu.
3. Dla każdej sekcji możesz przypisać trenerów, ustalić grupy wiekowe i harmonogram treningów.
4. Przełączanie między sekcjami odbywa się przez selektor w pasku nawigacji.

Sekcje można dezaktywować bez usuwania — dane historyczne zostaną zachowane.

---

## 4. Zawodnicy

### Dodawanie zawodnika
- **Ustawienia → Zawodnicy → Dodaj** — formularz z danymi osobowymi, kontaktowymi i sportowymi.
- Pola wymagane: imię, nazwisko, data urodzenia, PESEL (opcjonalnie), e-mail lub telefon opiekuna.
- Zdjęcie profilowe: JPG/PNG, max 1 MB.

### Import masowy
- Przygotuj plik CSV (UTF-8) zgodny z szablonem do pobrania w panelu.
- Prześlij go w sekcji **Import zawodników** — system zwaliduje dane i pokaże podgląd przed zapisem.

### Statusy
- **Aktywny** — bierze udział w treningach i zawodach.
- **Zawieszony** — tymczasowo nieaktywny (np. kontuzja).
- **Archiwalny** — odszedł z klubu; dane zachowane do celów sprawozdawczych.

---

## 5. Składki

### Definiowanie składek
1. Przejdź do **Składki → Typy składek**.
2. Utwórz typ (np. „Składka miesięczna", „Wpisowe") z kwotą i okresem rozliczeniowym.

### Naliczanie
- Automatyczne naliczanie następuje 1. dnia każdego okresu (miesiąc/kwartał/rok).
- Naliczenie ręczne: **Składki → Nalicz** — wybierz grupę zawodników i typ składki.

### Płatności
- Status: **Oczekująca**, **Opłacona**, **Przeterminowana**.
- Rejestracja wpłaty: wpisz datę i kwotę lub zaimportuj wyciąg bankowy (CSV).
- Przypomnienia e-mail: konfigurowane w **Składki → Ustawienia przypomnień** (np. 7 dni przed terminem, w dniu terminu, 7 dni po terminie).

### Raporty składkowe
- Zestawienie zaległości, wpływów miesięcznych, prognozy — dostępne w zakładce **Raporty → Składki**.

---

## 6. Wydarzenia

1. **Wydarzenia → Nowe wydarzenie** — podaj nazwę, datę, lokalizację, typ (mecz, turniej, obóz).
2. Dodaj uczestników ręcznie lub przypisz całą grupę/sekcję.
3. Możesz załączyć regulamin (PDF) i ustawić limit miejsc.
4. Uczestnicy otrzymają powiadomienie e-mail i push (jeśli włączone).
5. Po wydarzeniu dodaj wyniki lub raport — będzie widoczny w profilu zawodnika.

---

## 7. Treningi

### Harmonogram
- **Treningi → Harmonogram** — kalendarz tygodniowy z podziałem na grupy.
- Dodaj trening: data, godzina, lokalizacja, trener, opis.
- Treningi cykliczne: zaznacz powtarzalność (co tydzień, co 2 tygodnie).

### Obecność
- Trener zaznacza obecność na liście uczestników przed lub po treningu.
- Zawodnik może potwierdzić obecność w portalu zawodnika.
- Statystyki frekwencji dostępne w **Raporty → Treningi**.

### Konspekty
- Opcjonalnie trener może dołączyć konspekt treningu (tekst lub plik PDF).

---

## 8. Portal zawodnika

Każdy zawodnik (lub jego opiekun) może zalogować się do dedykowanego portalu z ograniczonym widokiem:

- **Mój profil** — podgląd i edycja danych kontaktowych.
- **Składki** — historia płatności, kwoty do zapłaty.
- **Kalendarz** — nadchodzące treningi i wydarzenia.
- **Obecność** — potwierdzanie obecności na treningach.
- **Dokumenty** — badania lekarskie, licencje, zgody RODO.
- **Powiadomienia** — wiadomości od zarządu i trenerów.

Dostęp do portalu wymaga aktywacji konta przez administratora klubu.

---

## 9. Raporty

Moduł raportów jest dostępny w menu **Raporty** i obejmuje:

| Raport | Opis |
|---|---|
| Składki | Zaległości, wpływy, prognoza |
| Frekwencja | Obecność na treningach wg grupy/zawodnika |
| Zawodnicy | Liczebność, statusy, przyrosty |
| Wydarzenia | Lista wydarzeń z frekwencją |
| Badania lekarskie | Zawodnicy z wygasającymi badaniami |
| Licencje | Status licencji zawodniczych |

Raporty można eksportować do formatu CSV lub PDF.

---

## 10. Badania lekarskie

1. **Zawodnicy → [zawodnik] → Badania** — dodaj wpis: data badania, data ważności, lekarz, załącznik (skan).
2. System automatycznie ostrzega 30 dni przed wygaśnięciem badania (powiadomienie + e-mail).
3. Zawodnik z przeterminowanym badaniem jest oznaczany na liście treningowej — trener widzi ostrzeżenie.
4. Raport zbiorczy: **Raporty → Badania lekarskie**.

---

## 11. Licencje

- **Zawodnicy → [zawodnik] → Licencje** — dodaj numer licencji, związek sportowy, datę ważności.
- Obsługiwane związki: PZS, PZPN, PZJ i inne — lista konfigurowalna.
- Powiadomienia o wygasających licencjach analogicznie do badań lekarskich.
- Eksport listy licencji do CSV — przydatny przy zgłoszeniach do zawodów.

---

## 12. RODO

Aplikacja wspiera zgodność z RODO:

- **Zgody** — podczas rejestracji zawodnika zbierane są zgody na przetwarzanie danych (treningowe, wizerunkowe, marketingowe). Każda zgoda jest wersjonowana i datowana.
- **Prawo dostępu** — zawodnik może w portalu pobrać swoje dane w formacie JSON.
- **Prawo do usunięcia** — administrator może zanonimizować profil zawodnika (dane osobowe zastępowane są losowym identyfikatorem). Operacja jest nieodwracalna.
- **Rejestr czynności przetwarzania** — dostępny w **Ustawienia → RODO → Rejestr**.
- **Polityka prywatności** — konfigurowalny link wyświetlany w stopce portalu.

---

## 13. Kopie zapasowe

- Dostępne dla administratora klubu w **Ustawienia → Kopie zapasowe**.
- Kopia obejmuje dane klubu (bez danych innych klubów w systemie).
- Format: archiwum SQL (.sql.gz).
- Kopie automatyczne: system tworzy kopię codziennie o 03:00 (konfigurowalne).
- Ręczne tworzenie kopii: kliknij **Utwórz kopię teraz**.
- Pobieranie i usuwanie kopii dostępne z poziomu listy.

> **Ważne:** Kopie zapasowe całego systemu (wszystkich klubów) są dostępne wyłącznie dla super administratora.

---

## 14. Klucze API

Kluby mogą integrować się z systemami zewnętrznymi przez REST API.

1. Przejdź do **Ustawienia → Klucze API**.
2. Kliknij **Generuj nowy klucz** — nadaj mu nazwę i wybierz zakresy uprawnień (scopes): `members:read`, `events:read`, `payments:read` itp.
3. Klucz wyświetla się jednokrotnie — skopiuj go i przechowuj bezpiecznie.
4. Klucz przesyłaj w nagłówku `Authorization: Bearer <klucz>`.
5. Limit zapytań: 100 req/min (konfigurowalny w planie subskrypcyjnym).
6. Klucz można dezaktywować lub usunąć w dowolnym momencie.

Szczegółowa dokumentacja endpointów dostępna jest w pliku `docs/api-reference.md`.
