# Cennik ClubDesk — propozycja do clubdesk.pl

> **Plik dla zespołu strony marketingowej clubdesk.pl** — zawiera analizę
> konkurencji, rekomendowaną strukturę cennika, copy do strony i sekcje FAQ.
> Ostatnia aktualizacja: maj 2026.

---

## 1. Analiza konkurencji (rynek PL + EU + US)

### Bezpośredni konkurenci (PL)

| System | Model cenowy | Cena (zł/m-c) | Wyróżniki | Słabości |
|---|---|---|---|---|
| **Klubduden** | Per zawodnik | 50–250 zł (zależy od liczby) | Polski, prosty UI | 1–2 sporty, brak płatności online |
| **eClub** | Ryczałt | 99–399 zł | Lokalna obsługa | Stara technologia, słaba mobilność |
| **mojaklasa** | Per szkółka | 79–199 zł | Skupienie na szkółkach | Brak federacji, brak WADA |
| **GoSport** | Freemium | 0 / 49–149 zł | Darmowy plan | Bez polskich bramek |

### Zagraniczni konkurenci (EU/US)

| System | Cena USD/EUR | Funkcje | Brakujące w PL |
|---|---|---|---|
| **TeamUp** (UK) | €15–€60/m-c | Multi-club, dobry UI | Brak polskich federacji + bramek |
| **Spond** (NO) | Freemium + €5/coach | Komunikator | Brak modułu opłat per zawodnik |
| **TeamSnap** (US) | $9.99–$49/team | Najpopularniejszy w US | UI po angielsku, USD |
| **Sportlyzer** (EE) | €19–€99/m-c | Dobre raporty | Wąska oferta sportów |
| **Smart Club Manager** (DE) | €40–€200/m-c | Rozbudowany | Drogi, niemiecki UI |

### Wnioski strategiczne

ClubDesk wchodzi w rynek **podgrzany przez Klubduden i eClub**, ale z trzema
nieprzykrytymi przewagami:

1. **49 dyscyplin sportowych** z pluginami (vs 1–3 u konkurencji)
2. **4 polskie bramki płatności** (P24 / PayU / Tpay / Stripe) zintegrowane natively
3. **Cross-club identity** — zawodnik logujący się raz do wszystkich klubów

---

## 2. Rekomendowana struktura cennika

### 6 planów + dodatki

| Plan | Cena/m-c | Cena/rok (rabat 17%) | Limit zawodników | Sekcje | Target |
|---|---|---|---|---|---|
| **Trial — 14 dni** | 0 zł | — | 30 | 1 | Każdy nowy klub |
| **Starter** | 39 zł | 390 zł | 50 | 1 | Małe szkółki, 1 sport |
| **Klub** ★ NAJPOPULARNIEJSZY | **89 zł** | 890 zł | 150 | 5 | Klub z kilkoma sekcjami |
| **Multi-Sport** | 179 zł | 1 790 zł | 500 | 15 | Duże kluby, akademie |
| **Enterprise** | 349 zł | 3 490 zł | bez limitu | bez limitu | Akademie, fundacje |
| **Federacja** | wycena | wycena | bez limitu | bez limitu | PZPN, PZKosz, sieci klubów |

### Add-ons (dla każdego planu)

| Dodatek | Cena | Co dostajesz |
|---|---|---|
| **Pakiet SMS — 100** | 25 zł/m-c | Powiadomienia o treningach |
| **Pakiet SMS — 500** | 99 zł/m-c | + przypomnienia o składkach |
| **Pakiet SMS — 2000** | 299 zł/m-c | + masowe akcje marketingowe |
| **Asystowane wdrożenie** | 990 zł jednorazowo | Dedykowany konsultant 5h |
| **Migracja z innego systemu** | bezpłatnie | (CSV / Excel / Klubduden) |
| **Szkolenie online** | 290 zł/sesja | 90 min, do 10 osób z klubu |

---

## 3. Copy do strony — sekcje główne

### Hero

> # Pełne zarządzanie klubem sportowym w jednym miejscu
>
> ClubDesk to system dla 49 dyscyplin sportowych — z polskimi federacjami,
> płatnościami online i pełną obsługą zawodnika od zapisu do dyplomu.
>
> **Wypróbuj 14 dni za darmo · bez karty kredytowej · anuluj kiedy chcesz**
>
> [▶ Rozpocznij za darmo] [📞 Porozmawiaj z nami]

### Sekcja "Dlaczego ClubDesk"

#### 1. 49 dyscyplin z gotowymi modułami

Piłka nożna, koszykówka, siatkówka, judo, karate, tenis, pływanie, lekkoatletyka,
żeglarstwo, jeździectwo, narciarstwo i 38 innych — każda z własnymi federacjami,
licencjami zawodniczymi i specyficznymi raportami (mecze, walki, czasy).

#### 2. Polskie płatności online z 4 bramek

Zawodnik klika "Zapłać" w portalu — wybiera: Przelewy24 / PayU / Tpay / Stripe.
Wpłata księguje się automatycznie w klubie. Webhook + retry + reconciliation.
Klub nie musi sprawdzać przelewów ręcznie.

#### 3. Multi-klub w 1 koncie zawodnika

Junior trenuje w klubie A (piłka) i klubie B (basen)?
Loguje się raz, przełącza sekcje dropdown-em. Klub widzi historię z obu —
medical, opłaty, frekwencja per sekcja, ale wspólny profil.

#### 4. Polski WADA anti-doping

Pełen zgodny z regulaminem PZSS / WADA system zgód anty-dopingowych dla
zawodników startujących w zawodach federacyjnych. Mało który system PL
to oferuje.

#### 5. Bezpieczeństwo enterprise

GDPR-ready, AES-256-GCM dla danych medycznych, RBAC per rola, izolacja
multi-tenant (klub A nigdy nie widzi danych klubu B). Audit log każdej akcji.

---

### Sekcja "Cennik" — układ kart

**[Render z `/cennik` w aplikacji — 6 kart side-by-side, toggle Monthly/Yearly]**

> Wszystkie plany zawierają:
> - Bezpłatne aktualizacje
> - Polska obsługa techniczna (email)
> - Hostowane w Polsce (Plesk, OVH/Hetzner)
> - Backup automatyczny codziennie
> - Eksport danych w każdym momencie (CSV, JSON)

### Sekcja "Porównanie funkcji" — tabela

| Funkcja | Trial | Starter | **Klub** | Multi-Sport | Enterprise |
|---|---|---|---|---|---|
| Liczba zawodników | 30 | 50 | 150 | 500 | bez limitu |
| Liczba sekcji | 1 | 1 | 5 | 15 | bez limitu |
| Płatności online | ❌ | ✅ P24/PayU/Tpay | ✅ + Stripe | ✅ wszystkie | ✅ wszystkie |
| SMS w cenie | ❌ | ❌ | ✅ 100/m-c | ✅ 500/m-c | ✅ 2000/m-c |
| API REST | ❌ | ❌ | ❌ | ✅ | ✅ |
| Custom branding | ❌ | ❌ | ✅ CSS | ✅ CSS | ✅ White-label |
| Anti-doping (WADA) | ❌ | ❌ | ✅ | ✅ | ✅ |
| Moduł medyczny | ✅ | ✅ | ✅ pełen | ✅ pełen | ✅ pełen |
| Raporty PDF z brandingiem | ❌ | ✅ podstawowe | ✅ | ✅ pełne | ✅ pełne |
| Kopie zapasowe | ❌ | ✅ tygodniowo | ✅ codziennie | ✅ codziennie | ✅ + on-demand |
| Wsparcie | Społeczność | Email | Email priority | Email + tel. | Dedykowany opiekun |
| SLA | — | — | — | — | 99,5% |

---

## 4. FAQ — sekcja "Najczęstsze pytania"

### Jak wygląda płatność?

Po wybraniu planu wystawiamy fakturę VAT w PLN ze stawką 23%.
Płatność: przelew tradycyjny lub karta przez Stripe. Subskrypcja odnawia się
automatycznie — przed każdym odnowieniem dostajesz email z możliwością zmiany planu.

### Mogę przejść między planami?

Tak — w każdej chwili. Upgrade działa od razu, downgrade od następnego okresu
rozliczeniowego (zachowując już zakupiony okres). Proporcjonalne rozliczenie
robimy automatycznie.

### Co po 14 dniach trial?

Email 3 dni przed końcem z propozycją wyboru planu. Bez decyzji konto przechodzi
w tryb tylko-do-odczytu (dane bezpieczne, brak utraty). Możesz wybrać plan
w dowolnym momencie i wrócić do pełnej funkcjonalności.

### Czy są ukryte koszty?

**Nie.** Cena = wszystko, co widzisz w cenniku. Kluczowe punkty:

- **SMS** w planie Klub+ są w pakiecie (limit miesięczny zgodny z planem)
- **Płatności online** — prowizje pobiera bramka (Przelewy24 / PayU / Tpay / Stripe),
  ClubDesk nie pobiera nic dodatkowo. To Ty negocjujesz stawki z bramką
- **Email** — w planie

### Jak migracja z innego systemu?

**Bezpłatna** dla planów Klub i wyżej. Importujemy z:

- CSV / Excel (uniwersalne)
- Klubduden (eksport z ich panelu)
- TeamSnap (eksport JSON)
- eClub (eksport CSV)
- Pliki Excel od księgowej / sekretarza

Migracja zawiera: zawodnicy, wpłaty, historia płatności, sekcje sportowe.
Trwa 1–3 dni robocze. Konsultant pomaga z mapowaniem pól.

### Faktura VAT?

Tak, każda subskrypcja jest fakturowana z polską stawką **VAT 23%**.
Faktury w PDF dostajesz mailem co miesiąc / rok zgodnie z planem.
Możesz pobrać archiwum z panelu Master Admina.

### Dane są bezpieczne?

- Hosting w Polsce (Plesk + OVH lub Hetzner DC w Warszawie)
- Pełen GDPR — RODO compliance
- Szyfrowanie AES-256-GCM dla danych medycznych
- Backup codziennie + retention 30 dni
- Eksport wszystkich danych w każdym momencie (CSV, JSON)
- 2FA dla administratorów (TOTP)

### Co jeśli klub urośnie ponad limit planu?

Na 7 dni przed osiągnięciem limitu otrzymujesz email z propozycją upgrade.
Po przekroczeniu nie ma utraty danych — system automatycznie blokuje dodawanie
nowych zawodników, ale wszystko inne działa. Po upgrade limit jest natychmiast
podwyższony.

---

## 5. CTA na całej stronie

### Główny przycisk (hero, footer)

> **Rozpocznij za darmo** — przekierowanie do `/register` z preselected planem trial

### Drugi przycisk

> **Porozmawiajmy** — `mailto:kontakt@clubdesk.pl?subject=Rozmowa%20o%20ClubDesk`

### Sticky bar dla returning visitors

> "Wciąż się zastanawiasz? **Wypróbuj 14 dni za darmo** — anuluj kiedy chcesz"

---

## 6. Sekcja "Klienci" / Social proof (do dodania gdy będą)

Pole na 4-6 logotypów + krótkie cytaty:

> "ClubDesk to dla nas oszczędność 8h/m-c na księgowości." — *Trener X, Klub Y*

> "Wreszcie zawodnicy płacą online — odpada 30% opóźnień." — *Skarbnik X, Klub Z*

---

## 7. Sekcja "Dla federacji"

> ## Jesteś federacją sportową lub organizatorem ligi?
>
> ClubDesk to platforma którą Twoja federacja może udostępnić wszystkim zrzeszonym
> klubom — z **scentralizowanym panelem federacji**, **SSO** i **dedykowanym SLA**.
>
> Wycena indywidualna od 5 000 zł / rok dla 50+ klubów.
>
> [Umów rozmowę z dyrektorem](mailto:federacje@clubdesk.pl)

Argumenty:
- Każdy klub ma swój własny ClubDesk + federacja widzi wszystkie
- SSO (jeden login do wszystkich klubów w federacji)
- Branding federacji + branding klubu
- Scentralizowane raporty dla zarządu federacji
- API do integracji z systemem federacji

---

## 8. Wdrożenie technologiczne — co zrobiliśmy w aplikacji

### Migracja DB

Plik: `database/migrations/052_pricing_overhaul.sql`

- Deactivuje stare 4 plany (trial/basic/standard/premium) — zachowuje historię
- Wstawia nowe 6 planów (trial_v2, starter, club, multi_sport, enterprise, federation)
- Z polami JSON w `features` zawierającymi tags dla badge-ów (`NAJPOPULARNIEJSZY`, `TRIAL`)
- Idempotent: ON DUPLICATE KEY UPDATE

### Strona w aplikacji: `/cennik` i `/pricing`

Plik: `app/Controllers/PricingController.php` + `app/Views/pricing/index.php`

- 6 kart side-by-side z toggle Monthly / Yearly
- Auto-rendering z DB (zmiany planu w panelu Master Admin → live na cenniku)
- Toggle JS przepisuje ceny + zmienia stopkę "miesięcznie" / "rocznie"
- Card "Klub" ma border-primary + badge `NAJPOPULARNIEJSZY`
- Plan "Federacja" ma CTA `mailto:` zamiast `/register`
- FAQ sekcja na dole (5 pytań w accordion-ie)

### Master Admin

Bez zmian — istniejące UI zarządzania planami w `/admin/platform/plans` działa
z nowymi danymi.

### Wdrożenie produkcyjne

```bash
cd /var/www/vhosts/portal.clubdesk.pl/httpdocs && \
git pull origin main && \
mysql cd_ < database/migrations/052_pricing_overhaul.sql && \
echo "✓ Cennik zaktualizowany"
```

Po tym strona `/cennik` na portalu pokazuje nowe plany.

---

## 9. Kolejne kroki po stronie marketingowej clubdesk.pl

- [ ] Przeniesienie HTML cennika do strony marketingowej (alt: iframe `/cennik` z portalu)
- [ ] Skonfigurowanie analityki konwersji (GA4 events na "Wybierz plan")
- [ ] A/B test cen — Starter 39 vs 49, Klub 89 vs 99
- [ ] Sekcja "Klienci" z 4-6 logotypami (po pozyskaniu pierwszych klubów)
- [ ] Pre-launch waitlist dla planu Federacja (zwiększa wartość — "limited spots")
- [ ] SEO: dedicated landing pages per dyscyplina (`/clubdesk-dla-pilki-noznej`,
      `/clubdesk-dla-tenisa`) — używamy 49 sportów z systemu

---

**Kontakt do dyskusji**: kontakt@clubdesk.pl
