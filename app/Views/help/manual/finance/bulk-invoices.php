<?php /** finance / bulk-invoices */ ?>
<p class="lead">Faktury masowe pozwalają wygenerować setki dokumentów jednym kliknięciem — np. roczne faktury członkowskie dla całej bazy. Operacja działa w tle i informuje o postępie w czasie rzeczywistym.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>W <strong>Finanse → Faktury → Operacje masowe</strong> kliknij <em>+ Generuj masowo</em>.</li>
    <li>Wybierz zakres odbiorców: <em>cała baza</em>, <em>konkretna sekcja</em>, <em>członkowie z aktywną składką</em>.</li>
    <li>Wybierz pozycje na fakturze (z listy konfiguracyjnej lub szablon).</li>
    <li>Określ daty (wystawienia, sprzedaży, płatności).</li>
    <li>Sprawdź podgląd dla pierwszych 5 odbiorców.</li>
    <li>Kliknij <strong>Generuj</strong>. Operacja działa w tle (typowo 5-15 minut dla 500 faktur).</li>
</ol>

<div class="manual-callout manual-callout-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Numeracja.</strong> Faktury masowe otrzymują kolejne numery z serii klubu. Jeśli wstrzymasz operację w połowie, możesz zostać z luką w numeracji — zabezpiecz się testem na 5 fakturach.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy mogę wysłać faktury masowo?</summary>
        <div class="faq-body">Tak — opcja <em>Wyślij e-mailem do odbiorców</em> robi to po wygenerowaniu.</div>
    </details>
    <details>
        <summary>Co z fakturami dla osób nieaktywnych?</summary>
        <div class="faq-body">System pomija członków zarchiwizowanych. Możesz nadpisać tę zasadę w konfiguracji.</div>
    </details>
    <details>
        <summary>Czy mogę cofnąć cały batch?</summary>
        <div class="faq-body">W ciągu 24h — tak (jednym kliknięciem). Po tym czasie tylko fakturami korygującymi.</div>
    </details>
</div>
