# Service Level Agreement (SLA) ClubDesk

**Sendormeco Holding Sp. z o.o., NIP 5252866457, KRS 0000906110, ul. Złota 75A/7, 00-819 Warszawa**

Wersja: 1.0
Data wejścia w życie: 1 maja 2026 r.

---

## § 1. Postanowienia ogólne

1. Niniejszy Service Level Agreement (dalej: „SLA") określa parametry techniczne i jakościowe świadczenia usługi ClubDesk przez **Sendormeco Holding Sp. z o.o.** (NIP 5252866457, KRS 0000906110, ul. Złota 75A/7, 00-819 Warszawa) na rzecz Klubów korzystających z platformy.
2. SLA stanowi załącznik do Regulaminu świadczenia usług drogą elektroniczną i jest jego integralną częścią.

## § 2. Definicje

1. **Miesięczny okres rozliczeniowy** – pełny miesiąc kalendarzowy.
2. **Dostępność (Uptime)** – procent czasu w miesięcznym okresie rozliczeniowym, w którym Usługa jest sprawna i dostępna dla Klubów, liczony według wzoru: `(Total minutes - Downtime minutes) / Total minutes × 100%`.
3. **Niedostępność (Downtime)** – każdy okres, w którym Usługa nie jest osiągalna z sieci publicznej lub działa z błędem 5xx dla przynajmniej 50% żądań w danej minucie, z wyjątkami określonymi w § 7.
4. **Okno serwisowe** – zaplanowany czas prac konserwacyjnych, w trakcie którego Usługa może być częściowo lub całkowicie niedostępna; nie jest wliczane do Downtime.
5. **Incydent** – nieplanowane zdarzenie powodujące Downtime lub istotną degradację Usługi.
6. **Zgłoszenie wsparcia (ticket)** – wiadomość Klubu kierowana na wsparcie@clubdesk.pl lub przez panel.
7. **Response Time** – czas pierwszej merytorycznej reakcji Procesora na zgłoszenie wsparcia.
8. **RTO (Recovery Time Objective)** – maksymalny dopuszczalny czas przywrócenia Usługi po krytycznym Incydencie.
9. **RPO (Recovery Point Objective)** – maksymalna dopuszczalna utrata danych mierzona w czasie (przed momentem awarii).

## § 3. Dostępność Usługi

1. Sendormeco zobowiązuje się do utrzymywania miesięcznej dostępności Usługi na poziomie nie niższym niż **99,5%**.
2. Dostępność mierzona jest niezależnie dla każdego klubu (na podstawie pingu HTTPS do https://app.clubdesk.pl/health oraz testów syntetycznych z co najmniej dwóch lokalizacji w UE).
3. Dane o dostępności udostępniane są publicznie na stronie statusu: https://status.clubdesk.pl.

## § 4. RTO i RPO

| Parametr | Wartość |
|---|---|
| **RTO** – maksymalny czas przywrócenia Usługi po krytycznym Incydencie | **4 godziny** |
| **RPO** – maksymalna utrata danych | **24 godziny** |

Kopie zapasowe są wykonywane co najmniej raz na dobę. W godzinach roboczych dla zmian o wysokim znaczeniu (np. importy danych, masowe zmiany) wykonywane są dodatkowe snapshoty zapewniające faktyczny RPO znacznie poniżej 24 godzin.

## § 5. Okno serwisowe

1. Planowane prace konserwacyjne wykonywane są w oknie serwisowym: **niedziela, godz. 02:00 – 06:00 czasu CET/CEST**.
2. O każdym planowanym oknie serwisowym Sendormeco powiadamia Klubów z co najmniej **48-godzinnym wyprzedzeniem** za pośrednictwem:
   a) panelu administratora klubu (banner informacyjny),
   b) wiadomości e-mail wysłanej do Administratora Klubu,
   c) strony statusu https://status.clubdesk.pl.
3. W wyjątkowych okolicznościach (np. krytyczna podatność bezpieczeństwa) Sendormeco może przeprowadzić nieplanowane prace serwisowe – w takim wypadku powiadomienie odbywa się z możliwie najkrótszym wyprzedzeniem, a czas takich prac liczony jest do Downtime, chyba że Klub został powiadomiony co najmniej 4 godziny wcześniej i prace nie przekroczyły 30 minut.

## § 6. Wsparcie techniczne

| Plan | Kanał | Response Time (godziny robocze pon.–pt. 9:00–17:00 CET) | Tryb krytyczny |
|---|---|---|---|
| **Basic** | e-mail: wsparcie@clubdesk.pl | do **48 godzin** | – |
| **Pro** | e-mail + formularz w panelu | do **24 godzin** | priorytet wysoki |
| **Enterprise** | dedykowany opiekun + e-mail + telefon | do **4 godzin** (24/7 dla incydentów krytycznych) | priorytet najwyższy + status update co 2h |

Tryb krytyczny dotyczy Incydentów uniemożliwiających korzystanie z kluczowych funkcji (logowanie, dostęp do danych, płatności). Klub zobowiązuje się do oznaczenia zgłoszenia jako „krytyczne" wyłącznie w uzasadnionych przypadkach.

## § 7. Wyłączenia odpowiedzialności

Do Downtime nie wlicza się okresów niedostępności wynikłych z:
1. **Siły wyższej** (force majeure), w tym katastrof naturalnych, blackout-ów energetycznych, ataków terrorystycznych, decyzji organów państwowych;
2. **Globalnych awarii dostawców infrastruktury** (np. AWS region-wide outage), pod warunkiem że Sendormeco podejmie udokumentowane działania mające na celu przeniesienie usługi do innego regionu w ramach RTO;
3. **Ataków cybernetycznych pochodzenia zewnętrznego** o skali wykraczającej poza możliwości technicznego zabezpieczenia (DDoS przekraczający 100 Gbps, 0-day exploits dostawców);
4. **Działań lub zaniechań Klubu**: błędna konfiguracja konta, przekroczenie limitów planu, nieautoryzowane modyfikacje API, nadużycia (scraping, brute force);
5. **Problemów po stronie infrastruktury Klubu**: brak łączności internetowej, awarie urządzeń końcowych, błędna konfiguracja DNS;
6. **Okien serwisowych** zapowiedzianych zgodnie z § 5.

## § 8. Bonifikaty z tytułu naruszenia SLA (Service Credits)

1. W przypadku, gdy dostępność w danym miesiącu spadnie poniżej 99,5%, Klub uprawniony jest do otrzymania bonifikaty (credits) na poczet kolejnej faktury:

| Dostępność miesięczna | Credit |
|---|---|
| 99,5% – 99,0% | 10% miesięcznej opłaty abonamentowej |
| 99,0% – 98,0% | 25% miesięcznej opłaty abonamentowej |
| 98,0% – 95,0% | 35% miesięcznej opłaty abonamentowej |
| poniżej 95,0% | 50% miesięcznej opłaty abonamentowej |

   Wzór: **10% za każdy 1% poniżej 99,5%, max 50% opłaty miesięcznej**.

2. Bonifikata przysługuje na wniosek Klubu złożony na adres kontakt@clubdesk.pl **w terminie 30 dni od końca okresu rozliczeniowego**, w którym wystąpiła niedostępność.
3. Wniosek powinien zawierać: identyfikator klubu, zakres czasowy niedostępności, oczekiwaną wysokość bonifikaty (opcjonalnie). Sendormeco rozpatruje wniosek w terminie 14 dni.
4. Łączna bonifikata w danym miesiącu nie może przekroczyć 50% opłaty abonamentowej za ten miesiąc.
5. Bonifikaty wykluczają dochodzenie innych roszczeń z tego samego tytułu (klauzula wyłączności).

## § 9. Pomiar i raportowanie

1. Sendormeco udostępnia publicznie raporty dostępności na stronie https://status.clubdesk.pl. Dane uptime są generowane na podstawie niezależnych testów syntetycznych.
2. Klub Enterprise otrzymuje miesięczny raport SLA na e-mail Administratora Klubu (PDF).
3. W razie sporu co do wysokości dostępności rozstrzygające są dane zarejestrowane przez wewnętrzne narzędzia monitorujące Sendormeco wraz z logami niezależnych dostawców usług monitorujących.

## § 10. Zmiany SLA

1. SLA może zostać zaktualizowane wraz z rozszerzeniem oferty (nowe plany, regiony, RTO/RPO). Każda zmiana jest wersjonowana i publikowana pod adresem https://clubdesk.pl/legal/sla z co najmniej 30-dniowym wyprzedzeniem.
2. Klub ma prawo wypowiedzieć umowę w razie istotnej zmiany SLA na warunkach mniej korzystnych, zgodnie z § 10 ust. 7 Regulaminu.
