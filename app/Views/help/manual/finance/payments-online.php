<?php /** finance / payments-online */ ?>
<p class="lead">Płatności online to wygodny sposób ściągania składek — członek płaci kartą, BLIK-iem lub przelewem w 30 sekund, a środki trafiają na konto klubu w 1-3 dni roboczych. ClubDesk integruje się ze Stripe, Przelewy24 (P24), PayU i Tpay.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Wejdź w <strong>Finanse → Bramki płatności → + Dodaj bramkę</strong>.</li>
    <li>Wybierz dostawcę (Stripe / P24 / PayU / Tpay).</li>
    <li>Zaloguj się do panelu dostawcy i wygeneruj klucze API (instrukcje wewnątrz formularza).</li>
    <li>Wklej klucze do ClubDesk i ustaw konto bankowe do wypłat.</li>
    <li>Włącz tryb produkcyjny (po testach w sandbox).</li>
    <li>Od tej pory każda faktura zawiera link „Zapłać online".</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/finance/gateways</div>
    <div class="manual-mockup-content">
                <h6 class="mb-3"><i class="bi bi-credit-card"></i> Bramki płatności</h6>
                <div class="row g-3">
                    <div class="col-md-6"><div class="card border-success"><div class="card-body"><div class="d-flex justify-content-between"><strong>Stripe</strong><span class="badge bg-success">Aktywna</span></div><small class="text-muted">Karty Visa/MC, Apple Pay, Google Pay</small><div class="mt-2 small">Prowizja: 1.4% + 0,75 zł</div><button class="btn btn-sm btn-outline-secondary mt-2">Konfiguruj</button></div></div></div>
                    <div class="col-md-6"><div class="card border-success"><div class="card-body"><div class="d-flex justify-content-between"><strong>Przelewy24</strong><span class="badge bg-success">Aktywna</span></div><small class="text-muted">BLIK, przelew, P24Now</small><div class="mt-2 small">Prowizja: 1.4%</div><button class="btn btn-sm btn-outline-secondary mt-2">Konfiguruj</button></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><div class="d-flex justify-content-between"><strong>PayU</strong><span class="badge bg-secondary">Nieaktywna</span></div><small class="text-muted">Karta, BLIK, raty PayU</small><div class="mt-2 small">Prowizja: 1.5%</div><button class="btn btn-sm btn-primary mt-2">+ Skonfiguruj</button></div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><div class="d-flex justify-content-between"><strong>Tpay</strong><span class="badge bg-secondary">Nieaktywna</span></div><small class="text-muted">Karta, BLIK, przelew</small><div class="mt-2 small">Prowizja: 1.3%</div><button class="btn btn-sm btn-primary mt-2">+ Skonfiguruj</button></div></div></div>
                </div>
                <div class="alert alert-info mt-3 small mb-0"><i class="bi bi-info-circle"></i> Możesz mieć włączonych kilka bramek równocześnie — członek wybiera preferowaną podczas płatności.</div>
            </div>
    <div class="manual-mockup-caption">Konfiguracja bramek płatności z aktywnymi i dostępnymi dostawcami.</div>
</div>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> Stripe Connect.</strong> Stripe oferuje najtańsze prowizje w UE (1.4% + 0,25 EUR) i błyskawiczne wypłaty (1-2 dni). Polecany jako pierwszy wybór dla klubów PL.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Kto płaci prowizję?</summary>
        <div class="faq-body">Domyślnie klub. Możesz włączyć opcję <em>Prowizja dodawana do faktury</em> — wtedy zapłaci członek (np. +1,5%).</div>
    </details>
    <details>
        <summary>Jaki czas wypłaty?</summary>
        <div class="faq-body">Stripe — 1-2 dni rob., P24 — 1 dzień rob., PayU — 1-3 dni rob., Tpay — 1 dzień rob.</div>
    </details>
    <details>
        <summary>Czy mogę zwrócić płatność?</summary>
        <div class="faq-body">Tak — w <em>Finanse → Płatności → [płatność] → Zwrot</em>. Proces zajmuje 5-10 dni roboczych.</div>
    </details>
</div>
