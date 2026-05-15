<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Wypłaty i rozliczenia</h1>
<p class="lead">
    Kiedy raport prowizji zostanie zatwierdzony przez zarząd, przechodzi do
    fazy wypłaty. Ta strona pokazuje, jak działa cały proces — od akceptacji
    do przelewu — i co robić w sytuacjach spornych.
</p>

<h2>Harmonogram wypłat klubu</h2>
<p>
    Klub może mieć różne polityki wypłat:
</p>
<ul>
    <li><strong>Comiesięcznie</strong> — najczęstsze rozwiązanie. Wypłaty
        do 10-tego dnia kolejnego miesiąca (po zatwierdzeniu raportu).</li>
    <li><strong>Co dwa tygodnie</strong> — rzadziej, popularne w dużych klubach
        z wieloma trenerami pełnoetatowymi.</li>
    <li><strong>Po fakturze</strong> — dla trenerów-osób fizycznych prowadzących
        działalność: wypłata realizowana po dostarczeniu faktury VAT.</li>
</ul>

<h2>Twoje dane do wypłaty</h2>
<p>
    W zakładce <em>Mój profil → Dane rozliczeniowe</em> uzupełniasz: pełną nazwę,
    NIP, REGON (opcjonalnie), adres siedziby, numer rachunku bankowego (IBAN
    + SWIFT jeśli zagraniczny). Numer rachunku jest <strong>maskowany</strong> w
    interfejsie — widzisz tylko ostatnie 4 cyfry.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/payouts</div>
    <div class="manual-mockup-content">
        <h6>Historia wypłat</h6>
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr><th>Okres</th><th>Kwota</th><th>Status</th><th>Data wypłaty</th><th>Rachunek</th><th>Dokument</th></tr>
            </thead>
            <tbody>
                <tr><td>Maj 2026</td><td>5 187 zł</td><td><span class="badge bg-warning text-dark">W trakcie</span></td><td>—</td><td>****1234</td><td>—</td></tr>
                <tr><td>Kwiecień 2026</td><td>4 967,75 zł</td><td><span class="badge bg-success">Wypłacono</span></td><td>2026-05-08</td><td>****1234</td><td><a><i class="bi bi-file-earmark-pdf"></i> Pokwit.</a></td></tr>
                <tr><td>Marzec 2026</td><td>4 720,50 zł</td><td><span class="badge bg-success">Wypłacono</span></td><td>2026-04-10</td><td>****1234</td><td><a><i class="bi bi-file-earmark-pdf"></i> Pokwit.</a></td></tr>
                <tr><td>Luty 2026</td><td>4 100,00 zł</td><td><span class="badge bg-success">Wypłacono</span></td><td>2026-03-09</td><td>****1234</td><td><a><i class="bi bi-file-earmark-pdf"></i> Pokwit.</a></td></tr>
                <tr><td>Styczeń 2026</td><td>3 850,00 zł</td><td><span class="badge bg-success">Wypłacono</span></td><td>2026-02-08</td><td>****1234</td><td><a><i class="bi bi-file-earmark-pdf"></i> Pokwit.</a></td></tr>
            </tbody>
        </table>
        <div class="d-flex justify-content-between mt-2">
            <small class="text-muted">Łącznie wypłacono w 2026: <strong>17 638,25 zł</strong></small>
            <button class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i> Eksport CSV roczny</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: historia wypłat z pokwitowaniami w PDF.</div>
</div>

<h2>Pokwitowanie wypłaty</h2>
<p>
    Po każdej zaksięgowanej wypłacie dostaniesz PDF — <em>pokwitowanie</em> — z
    danymi: okres rozliczeniowy, kwota, data przelewu, numer dokumentu w
    księgowości klubu, podpis elektroniczny. Pokwitowanie jest ważnym
    dokumentem księgowym i warto trzymać kopię u siebie.
</p>

<h2>Faktury (dla działalności gospodarczej)</h2>
<p>
    Jeżeli rozliczasz się przez fakturę VAT, klub może wymagać dostarczenia
    faktury przed realizacją wypłaty. ClubDesk pomaga w tym przez moduł
    <em>Faktury wystawione</em>:
</p>
<ol>
    <li>Otwierasz zatwierdzony raport prowizji.</li>
    <li>Klikasz <em>"Wygeneruj proforma"</em> — system tworzy dane do faktury.</li>
    <li>Wystawiasz fakturę w swoim systemie (lub używasz wbudowanego generatora,
        jeśli klub kupił moduł "Faktury trenerów").</li>
    <li>Wgrywasz PDF faktury do raportu — księgowość klubu zaakceptuje go i
        zwolni wypłatę.</li>
</ol>

<h2>Wstrzymanie wypłaty</h2>
<p>
    Wypłata może zostać wstrzymana w trzech sytuacjach:
</p>
<ul>
    <li><strong>Brak faktury</strong> — gdy klub wymaga, a Ty jeszcze nie
        wystawiłeś.</li>
    <li><strong>Spór o kwotę</strong> — gdy zgłosiłeś uwagę do raportu i
        nie została rozstrzygnięta.</li>
    <li><strong>Zaległości</strong> — np. niezwrócony sprzęt klubu, niewypłacone
        zaliczki. Klub ma prawo zająć część wypłaty na pokrycie.</li>
</ul>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Wstrzymanie wypłaty jest <em>tymczasowe</em>, nie "anulowanie". Po
    rozstrzygnięciu sytuacji klub musi wypłacić należność. Jeżeli zarząd
    bezpodstawnie blokuje wypłatę &gt; 30 dni — masz prawo do reklamacji
    formalnej (przycisk <em>"Reklamacja wypłaty"</em>), która automatycznie
    eskaluje do najwyższego poziomu zarządu klubu.
</div>

<h2>Roczne podsumowanie</h2>
<p>
    Pod tabelą historii wypłat masz przycisk <em>"Eksport CSV roczny"</em> —
    plik zawiera wszystkie wypłaty z bieżącego roku, gotowy do importu do
    Twojego programu księgowego lub PIT-u.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Ustaw w preferencjach <em>powiadomienie e-mail po każdej wypłacie</em> —
    dostaniesz wiadomość natychmiast po zaksięgowaniu przelewu, z linkiem
    do pokwitowania PDF.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
