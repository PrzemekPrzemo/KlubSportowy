# KlubSportowy — Przewodnik super administratora

Dokument przeznaczony dla administratorów platformy (rola `super_admin`).

---

## 1. Zarządzanie klubami

### Lista klubów
- **Panel SA → Kluby** — tabela wszystkich zarejestrowanych klubów z informacjami: nazwa, plan, status subskrypcji, data rejestracji, liczba zawodników.
- Filtrowanie po statusie: aktywny, zawieszony, demo, wygasły.

### Tworzenie klubu
1. Kliknij **Dodaj klub**.
2. Wypełnij dane: nazwa, e-mail właściciela, plan subskrypcyjny.
3. System wyśle e-mail z linkiem aktywacyjnym do właściciela.

### Edycja i zawieszenie
- Edytuj dane klubu, zmień plan, przedłuż subskrypcję.
- **Zawieś klub** — blokuje logowanie użytkowników klubu; dane pozostają w bazie.
- **Usuń klub** — nieodwracalne usunięcie wszystkich danych klubu. Wymaga podwójnego potwierdzenia.

---

## 2. Katalog sportów

- **Panel SA → Sporty** — globalna lista dyscyplin sportowych dostępnych w systemie.
- Każdy sport posiada: nazwę, ikonę (klasa CSS / SVG), klucz systemowy (`sport_key`), flagę aktywności.
- Dodawanie nowej dyscypliny: podaj nazwę, klucz, ikonę. Po zapisie dyscyplina staje się dostępna dla wszystkich klubów.
- Dezaktywacja sportu ukrywa go z listy wyboru przy tworzeniu nowej sekcji, ale nie wpływa na istniejące sekcje.

---

## 3. Plany subskrypcyjne

### Definiowanie planów
- **Panel SA → Plany** — lista planów (np. Free, Basic, Pro, Enterprise).
- Każdy plan określa: nazwę, cenę, okres (miesiąc/rok), limity (liczba zawodników, liczba sekcji, pojemność załączników).

### Przypisywanie
- Plan przypisywany jest do klubu przy tworzeniu lub edycji.
- Zmiana planu obowiązuje od następnego okresu rozliczeniowego.

### Limity
- Gdy klub osiągnie limit planu (np. maks. zawodników), system blokuje dodawanie nowych z komunikatem o konieczności rozszerzenia planu.

---

## 4. Demo

- Każdy nowy klub może korzystać z 14-dniowego okresu demo (konfigurowalne w **Panel SA → Ustawienia → Demo**).
- W trybie demo dostępne są wszystkie moduły bez ograniczeń.
- Po wygaśnięciu demo klub jest proszony o wybór planu płatnego; dane nie są usuwane.
- Super administrator może ręcznie przedłużyć okres demo dla wybranego klubu.

---

## 5. Reklamy

- **Panel SA → Reklamy** — zarządzanie banerami reklamowymi wyświetlanymi w panelu klubowym (wyłącznie w planie Free).
- Dodaj reklamę: tytuł, grafika (JPG/PNG, max 500 KB), link docelowy, daty wyświetlania.
- Pozycje: `sidebar`, `top_banner`, `footer`.
- Statystyki: wyświetlenia i kliknięcia dostępne w tabeli reklam.
- Kluby z planem płatnym nie widzą reklam.

---

## 6. Log aktywności

- **Panel SA → Logi** — centralny rejestr zdarzeń systemowych.
- Rejestrowane akcje: logowanie, zmiana uprawnień, tworzenie/usuwanie klubu, eksport danych, tworzenie kopii zapasowych.
- Filtry: zakres dat, typ akcji, użytkownik, klub.
- Logi przechowywane przez 365 dni, potem automatycznie archiwizowane.
- Eksport logów do CSV.

---

## 7. Impersonation

Funkcja pozwala super administratorowi zalogować się jako dowolny użytkownik klubu bez znajomości jego hasła.

1. Przejdź do **Panel SA → Kluby → [klub] → Użytkownicy**.
2. Kliknij ikonę **Impersonuj** obok wybranego użytkownika.
3. System otworzy sesję w kontekście tego użytkownika — widoczny jest baner informujący o trybie impersonacji.
4. Aby zakończyć, kliknij **Powrót do konta SA** w banerze.

> **Uwaga:** Każda sesja impersonacji jest rejestrowana w logu aktywności (data, kto, kogo).

---

## 8. Kopie zapasowe

### Kopie systemowe
- **Panel SA → Kopie zapasowe** — pełna kopia bazy danych (wszystkie kluby).
- Tworzenie ręczne: kliknij **Utwórz kopię teraz** — generowany jest plik `.sql.gz`.
- Harmonogram automatyczny: codziennie o 03:00 (cron).
- Retencja: 30 ostatnich kopii (konfigurowalne).

### Pobieranie i usuwanie
- Lista kopii z datą, rozmiarem i możliwością pobrania / usunięcia.
- Pliki przechowywane w katalogu `storage/backups/` na serwerze.

### Przywracanie
- Przywracanie kopii wymaga dostępu CLI: `php cli/restore.php <plik>`.
- Przed przywróceniem system automatycznie tworzy kopię bieżącą jako punkt przywracania.

> **Bezpieczeństwo:** Dostęp do modułu kopii zapasowych posiada wyłącznie rola `super_admin`. Pobieranie plików jest chronione walidacją ścieżki (realpath).
