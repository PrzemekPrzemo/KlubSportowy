<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Mój raport prowizji</h1>
<p class="lead">
    Raport prowizji to comiesięczne zestawienie tego, ile zarobiłeś w klubie,
    z rozbiciem na sekcje i konkretne wydarzenia. Jest dostępny zawsze online,
    również do podglądu historycznego — przydatne do wystawienia faktury (jeśli
    działasz w ramach jednoosobowej działalności).
</p>

<h2>Otwarcie raportu</h2>
<p>
    W menu lewym znajdujesz pozycję <strong>Prowizje</strong>. Domyślnie
    pokazany jest bieżący miesiąc <em>w trakcie realizacji</em> (z licznikiem
    "ile do tej pory zarobiłeś"). Selektor okresu pozwala przeskoczyć na
    poprzedni miesiąc, kwartał, czy cały rok.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/commission?period=2026-04</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Raport prowizji — kwiecień 2026</h6>
            <div>
                <select class="form-select form-select-sm d-inline-block" style="width:auto;" disabled>
                    <option>Kwiecień 2026</option>
                </select>
            </div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Treningi</small><div class="h5 mb-0">28</div></div></div></div>
            <div class="col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Godziny</small><div class="h5 mb-0">47.5</div></div></div></div>
            <div class="col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Bonusy</small><div class="h5 mb-0">300 zł</div></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body p-2 text-center"><small>Razem do wypłaty</small><div class="h5 mb-0">5 187 zł</div></div></div></div>
        </div>
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr><th>Sekcja</th><th class="text-end">Godz.</th><th class="text-end">Stawka</th><th class="text-end">Podstawa</th><th class="text-end">Bonusy</th><th class="text-end">Razem</th></tr>
            </thead>
            <tbody>
                <tr><td>Skrzaty (U-9)</td><td class="text-end">15.5</td><td class="text-end">95 zł</td><td class="text-end">1 472,50 zł</td><td class="text-end">+147,25 zł</td><td class="text-end"><strong>1 619,75 zł</strong></td></tr>
                <tr><td>Orliki A (U-11)</td><td class="text-end">14.0</td><td class="text-end">90 zł</td><td class="text-end">1 260,00 zł</td><td class="text-end">—</td><td class="text-end"><strong>1 260,00 zł</strong></td></tr>
                <tr><td>Junior (U-15)</td><td class="text-end">stała</td><td class="text-end">1500 zł</td><td class="text-end">1 500,00 zł</td><td class="text-end">+300 zł (Bielsko 2. miejsce)</td><td class="text-end"><strong>1 800,00 zł</strong></td></tr>
                <tr><td>Młodzik (asyst.)</td><td class="text-end">8.0</td><td class="text-end">36 zł</td><td class="text-end">288,00 zł</td><td class="text-end">—</td><td class="text-end"><strong>288,00 zł</strong></td></tr>
            </tbody>
            <tfoot class="table-light">
                <tr><td colspan="5"><strong>Razem</strong></td><td class="text-end"><strong>4 967,75 zł</strong></td></tr>
            </tfoot>
        </table>
        <small class="text-muted">Status: <span class="badge bg-warning text-dark">do zatwierdzenia przez zarząd</span></small>
    </div>
    <div class="manual-mockup-caption">Mockup: szczegółowy raport prowizji za jeden miesiąc.</div>
</div>

<h2>Status raportu</h2>
<p>
    Raport przechodzi przez 4 stany:
</p>
<ol>
    <li><strong>W trakcie</strong> — miesiąc jeszcze trwa.</li>
    <li><strong>Do zatwierdzenia</strong> — miesiąc się skończył, zarząd ma 7 dni na akceptację.</li>
    <li><strong>Zatwierdzony</strong> — kwota jest finalna, oczekuje wypłaty.</li>
    <li><strong>Wypłacony</strong> — przelew zaksięgowany, raport oznaczony datą wypłaty.</li>
</ol>

<h2>Spór o kwotę</h2>
<p>
    Jeżeli kwota wygląda nieprawidłowo, klikasz <em>"Zgłoś uwagę do raportu"</em>.
    Otwiera się pole tekstowe — opisujesz, co Twoim zdaniem jest nie tak. Zarząd
    dostaje notyfikację i wraca do Ciebie z odpowiedzią. Do czasu rozstrzygnięcia
    status raportu to <em>"W sporze"</em>.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Im wcześniej zgłosisz uwagę, tym łatwiej ją rozstrzygnąć. Raport jest
    <em>zamykany</em> 14 dni po zakończeniu miesiąca — po tym terminie korekta
    wymaga zaangażowania księgowości i może opóźnić wypłatę.
</div>

<h2>Eksport</h2>
<p>
    Możesz wyeksportować raport jako PDF (do faktury) lub CSV (do własnej
    księgowości). Przycisk znajduje się w prawym górnym rogu raportu. Plik
    zawiera dane wystarczające do wystawienia faktury VAT lub rachunku.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    W <em>Mój profil → Dane rozliczeniowe</em> uzupełnij NIP/REGON i adres
    firmy — pojawią się one automatycznie w eksporcie PDF, gotowe do skopiowania
    na fakturę.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
