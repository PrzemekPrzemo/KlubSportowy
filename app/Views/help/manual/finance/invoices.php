<?php /** finance / invoices */ ?>
<p class="lead">Faktury VAT i rachunki klubowe generowane są automatycznie po opłaceniu składki lub na żądanie. ClubDesk obsługuje pełną zgodność z polskim prawem VAT, JPK_FA i KSeF.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>W <strong>Finanse → Faktury</strong> kliknij <em>+ Wystaw fakturę</em>.</li>
    <li>Wybierz odbiorcę (członek z bazy lub kontrahent zewnętrzny — sponsor, firma).</li>
    <li>Dodaj pozycje: opis, ilość, cena netto, stawka VAT. System obliczy brutto.</li>
    <li>Wybierz datę wystawienia i termin płatności.</li>
    <li>Opcjonalnie: numer rachunku bankowego do przelewu, dodatkowy komentarz.</li>
    <li>Kliknij <strong>Wystaw</strong> — faktura otrzymuje kolejny numer z serii klubu i jest wysyłana mailem na odbiorcę.</li>
</ol>

<div class="manual-callout manual-callout-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> KSeF od 2026.</strong> Od 2026 roku faktury B2B muszą być wystawiane przez Krajowy System e-Faktur. ClubDesk integruje się z KSeF API automatycznie — skonfiguruj w <em>Finanse → Integracje → KSeF</em>.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy mogę wystawić fakturę zbiorczą za cały rok?</summary>
        <div class="faq-body">Tak — w <em>Operacje masowe → Faktura zbiorcza</em>. Idealne dla rocznych składek.</div>
    </details>
    <details>
        <summary>Czy faktury są wysyłane automatycznie?</summary>
        <div class="faq-body">Po opłaceniu — tak. Możesz też włączyć autowysyłkę w dniu wystawienia (bez czekania na zapłatę).</div>
    </details>
    <details>
        <summary>Czy mogę cofnąć fakturę?</summary>
        <div class="faq-body">Tylko poprzez notę korygującą lub fakturę korygującą (zgodnie z ustawą o VAT). System prowadzi przez proces.</div>
    </details>
</div>
