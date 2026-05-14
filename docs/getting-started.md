# Pierwsze kroki w ClubDesk

Walkthrough dla nowego administratora klubu. Po przejściu przez 5 sekcji
poniżej Twój klub będzie gotowy do codziennej pracy: członkowie, składki,
treningi i komunikacja.

---

## 1. Pierwsze kroki — login i ustawienia klubu

Po rejestracji klubu otrzymasz e-mail z linkiem aktywacyjnym (ważny 24 h).
Kliknij go i ustaw silne hasło. Pierwsze logowanie odbywa się pod
`/auth/login` — użyj adresu administratora oraz hasła, które właśnie
ustawiłeś.

Po zalogowaniu przejdź do **Ustawienia → Dane klubu** i uzupełnij:

- nazwa wyświetlana, NIP/REGON, adres siedziby,
- e-mail kontaktowy i telefon (widoczne w stopce komunikacji),
- logo klubu (PNG/SVG, max 2 MB) oraz kolor brandingowy,
- strefa czasowa (domyślnie `Europe/Warsaw`).

Następnie w **Ustawienia → Moduły** włącz tylko te obszary, których
faktycznie będziesz używać — wyłączone moduły znikają z nawigacji
i nie generują powiadomień.

> Wskazówka: jeśli prowadzisz wiele sekcji sportowych, najpierw dodaj
> je w **Ustawienia → Sekcje sportowe**. Każda sekcja ma własnych
> trenerów, grupy i harmonogram.

[Zrzut: ekran ustawień klubu]

---

## 2. Dodawanie członków

ClubDesk obsługuje trzy sposoby dodawania zawodników/członków:

**Ręcznie** — **Członkowie → Dodaj**. Wypełnij imię, nazwisko, datę
urodzenia, e-mail/telefon opiekuna. Pole PESEL jest opcjonalne, ale
przydaje się przy generowaniu sprawozdań do związków sportowych.

**Import CSV** — przygotuj plik wg szablonu (do pobrania w widoku
**Członkowie → Import**). System waliduje dane wiersz po wierszu
i pokazuje podgląd zanim cokolwiek zapisze. Duplikaty po e-mailu są
oznaczane na czerwono.

**Portal samorejestracji** — wygeneruj publiczny link rejestracyjny
w **Ustawienia → Samorejestracja**. Rodzice/zawodnicy wypełniają
formularz samodzielnie, a Ty zatwierdzasz lub odrzucasz zgłoszenia
w kolejce moderacji.

Każdy członek może mieć status: *aktywny*, *zawieszony*
(np. kontuzja) lub *archiwalny* (odszedł z klubu — dane zachowane
do celów sprawozdawczych).

[Zrzut: lista członków + przycisk Import CSV]

---

## 3. Konfiguracja składek i płatności

Najpierw zdefiniuj **typy składek** w **Składki → Typy**: np.
„Składka miesięczna — junior 80 zł", „Wpisowe 150 zł". Każdy typ ma
kwotę, okres rozliczeniowy i opcjonalnie przypisaną grupę sekcji.

Naliczanie może być automatyczne (1. dnia każdego okresu) lub ręczne
(**Składki → Nalicz**). Przeterminowane składki podświetlają się
na czerwono w widoku członka i na dashboardzie.

**Bramki płatności** włącz w **Ustawienia → Płatności online**.
Obsługiwane: Przelewy24, Stripe, PayU. Wpisz API key/merchant id,
przetestuj na płatności 1 zł i włącz dla wszystkich członków.

**Przypomnienia automatyczne** — w **Składki → Przypomnienia**
zaznacz np. „7 dni przed terminem", „w dniu terminu", „7 dni po".
Wiadomości generuje się z szablonów (e-mail i opcjonalnie SMS).

[Zrzut: konfiguracja typów składek]

---

## 4. Treningi i wydarzenia

W **Treningi → Plan** dodawaj cykliczne wydarzenia (sala, godzina,
grupa, trener). System wykryje konflikty (ta sama sala/trener
o tej samej godzinie) i ostrzeże przed zapisem.

Lista obecności jest dostępna z poziomu pojedynczego treningu —
trener oznacza obecnych jednym kliknięciem na tablecie/telefonie.
Frekwencja agreguje się automatycznie i widać ją w profilu zawodnika
oraz w raportach miesięcznych.

**Wydarzenia jednorazowe** (zawody, obozy, eventy) dodajesz w
**Wydarzenia → Dodaj**. Można do nich zapisywać uczestników, pobierać
opłaty startowe i generować listy startowe do PDF.

[Zrzut: kalendarz treningów]

---

## 5. Komunikacja

ClubDesk wysyła powiadomienia trzema kanałami:

- **E-mail** — domyślnie aktywny, używa SMTP klubu (skonfiguruj w
  **Ustawienia → SMTP**) lub fallback przez systemowy mailer.
- **SMS** — wymaga doładowania konta SMS w **Ustawienia → SMS**
  (integracje: SMSAPI, Twilio). Używaj oszczędnie — kosztuje.
- **Powiadomienia in-app** — dzwoneczek w górnym pasku po
  zalogowaniu. Działa też w aplikacji mobilnej (Flutter).

W **Komunikacja → Szablony** edytuj treści: potwierdzenie zapisu,
przypomnienie o składce, odwołanie treningu. Zmienne typu
`{{member.first_name}}` podstawiają się automatycznie.

Wysyłka grupowa: **Komunikacja → Nowa wiadomość** — wybierz
odbiorców (cała sekcja, grupa wiekowa, lista zaległości) i kanał.
System pokaże szacunkowy koszt SMS przed wysyłką.

[Zrzut: kreator wiadomości grupowej]

---

## Co dalej?

- **Przewodnik administratora** — pełna referencja konfiguracji (`/help/admin`).
- **API** — integracje z systemami zewnętrznymi (`/help/api`).
- **Instalacja na Plesk** — jeśli hostujesz ClubDesk samodzielnie (`/help/installation`).

Pytania? Napisz na support@clubdesk.pl.
