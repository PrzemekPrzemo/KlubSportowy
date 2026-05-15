<?php
$page = [
    'title'        => 'Płatność online',
    'category'     => 'Zawodnik',
    'group'        => 'Płatności',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Płatność online</h1>
<p class="lead">Możesz zapłacić składkę kartą, BLIK-iem, Apple Pay albo Google Pay — bez wychodzenia z portalu. Całość trwa 30 sekund, pieniądze trafiają natychmiast, a faktura PDF jest gotowa w tle.</p>

<h2>Jak zapłacić — krok po kroku</h2>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź w <em>Składki</em>.</li>
    <li><span class="manual-step-num">2</span>Przy pozycji, którą chcesz opłacić, kliknij zielony przycisk <strong>Zapłać online</strong>.</li>
    <li><span class="manual-step-num">3</span>Wybierz metodę: karta, BLIK, Apple/Google Pay.</li>
    <li><span class="manual-step-num">4</span>Wpisz dane (lub autoryzuj BLIK-iem) i potwierdź.</li>
    <li><span class="manual-step-num">5</span>Gotowe — wrócisz do portalu z zielonym potwierdzeniem.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/payments/checkout</div>
    <div class="manual-mockup-content">
        <div class="mx-auto" style="max-width:480px;">
            <h5 class="mb-1">Płatność składki</h5>
            <p class="text-muted small mb-3">UKS Iskra · składka maj 2026</p>
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <small class="text-muted">Do zapłaty</small>
                        <h4 class="mb-0">120,00 zł</h4>
                    </div>
                    <i class="bi bi-shield-lock-fill text-success fs-3" title="Płatność zabezpieczona TLS"></i>
                </div>
            </div>

            <label class="form-label small">Numer karty</label>
            <input class="form-control mb-2" value="4242 4242 4242 4242">
            <div class="row g-2 mb-2">
                <div class="col-7">
                    <label class="form-label small">Data ważności</label>
                    <input class="form-control" value="12/28">
                </div>
                <div class="col-5">
                    <label class="form-label small">CVC</label>
                    <input class="form-control" value="123">
                </div>
            </div>
            <label class="form-label small">Imię i nazwisko z karty</label>
            <input class="form-control mb-3" value="ANNA KOWALSKA">

            <button class="btn btn-danger w-100 mb-2"><i class="bi bi-credit-card"></i> Zapłać 120,00 zł</button>
            <div class="text-center small text-muted">
                lub:
                <button class="btn btn-outline-dark btn-sm ms-1">BLIK</button>
                <button class="btn btn-outline-dark btn-sm">Apple Pay</button>
                <button class="btn btn-outline-dark btn-sm">G Pay</button>
            </div>
            <div class="text-center small text-muted mt-2">
                <i class="bi bi-lock"></i> Płatność obsługiwana przez Stripe / Przelewy24
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Bezpieczny formularz płatności — Twoje dane karty nie są zapisywane w portalu klubu.</div>
</div>

<h2>Bezpieczeństwo płatności</h2>
<p>Płatności obsługuje certyfikowany operator (Stripe lub Przelewy24 — zależy od klubu). Dane karty <strong>nie są zapisywane</strong> na serwerze klubu. Cała transakcja idzie szyfrowanym kanałem (TLS), a Twój bank potwierdza ją SMS-em / 3D Secure.</p>

<h2>BLIK krok po kroku</h2>
<ol>
    <li>Wybierz „BLIK" jako metodę.</li>
    <li>Otwórz aplikację bankową, wygeneruj 6-cyfrowy kod BLIK.</li>
    <li>Wpisz kod w portalu i kliknij Zapłać.</li>
    <li>Potwierdź transakcję w aplikacji bankowej (PIN / Face ID).</li>
</ol>

<div class="manual-tip">
    <strong>Zapamiętana karta.</strong> Możesz dać zgodę na zapamiętanie karty — wtedy następna płatność to dosłownie jedno kliknięcie. Karta jest szyfrowana po stronie operatora, nie po stronie klubu.
</div>

<h2>Co się stanie po zapłacie</h2>
<ul>
    <li>Składka od razu zmienia status na <span class="badge bg-success">Opłacone</span>.</li>
    <li>Faktura PDF jest generowana automatycznie — pojawi się w sekcji <em>Historia płatności</em>.</li>
    <li>Dostaniesz e-mail z potwierdzeniem i fakturą w załączniku.</li>
    <li>Karta członkowska natychmiast wraca do statusu Aktywna (jeśli była Nieaktywna).</li>
</ul>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Nie mogę dokończyć płatności — co robić?</summary>
    <p>Najczęściej powód to limit operacji online w banku albo źle wpisany kod 3D Secure. Spróbuj ponownie albo wybierz inną metodę (BLIK). Jeśli kwota została pobrana, ale w portalu widać „Niezapłacone" — napisz do klubu, sprawdzą bezpośrednio u operatora.</p>
</details>
<details>
    <summary>Czy mogę zapłacić za znajomego?</summary>
    <p>Możesz — dane karty nie muszą zgadzać się z danymi zawodnika. Faktura pójdzie na dane członka klubu (nie posiadacza karty).</p>
</details>
<details>
    <summary>Apple Pay / Google Pay nie działa</summary>
    <p>Sprawdź, czy używasz oficjalnej aplikacji (PWA) albo przeglądarki Safari (iOS) / Chrome (Android) — inne przeglądarki nie wspierają tych metod.</p>
</details>
