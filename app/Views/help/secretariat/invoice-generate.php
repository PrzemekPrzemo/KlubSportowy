<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Generowanie faktur</h1>
<p class="lead">
    Generowanie faktur (właściwie: rachunków lub faktur, zależnie od formy
    organizacyjnej klubu) jest sercem operacji finansowych sekretariatu.
    ClubDesk automatyzuje proces w 95% — Ty tylko sprawdzasz i akceptujesz
    paczkę, system robi resztę.
</p>

<h2>Generowanie cykliczne (raz w miesiącu)</h2>
<p>
    Najczęstszy scenariusz: pierwszego dnia każdego miesiąca rano (lub w
    pierwszy roboczy) generujesz faktury za bieżący miesiąc dla wszystkich
    aktywnych członków. Proces wygląda tak:
</p>
<ol>
    <li>Wchodzisz w <strong>Finanse → Faktury → Generuj paczkę</strong>.</li>
    <li>Wybierasz okres (np. maj 2026).</li>
    <li>System pokazuje preview — listę faktur do wygenerowania z kwotami.</li>
    <li>Sprawdzasz, klikasz <em>Zatwierdź</em>.</li>
    <li>System tworzy faktury, wysyła PDF e-mailem rodzicom, zapisuje w bazie.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/invoices/batch?period=2026-05</div>
    <div class="manual-mockup-content">
        <h6>Generowanie faktur — Maj 2026</h6>
        <div class="row g-2 mb-3">
            <div class="col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Aktywnych</small><div class="h5 mb-0">347</div></div></div></div>
            <div class="col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Do wygenerowania</small><div class="h5 mb-0">339</div></div></div></div>
            <div class="col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Wstrzymanych</small><div class="h5 mb-0">8</div></div></div></div>
            <div class="col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Suma brutto</small><div class="h5 mb-0">107 800 zł</div></div></div></div>
        </div>
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr><th>Członek</th><th>Sekcja</th><th>Plan</th><th class="text-end">Kwota</th><th>Ulgi</th><th class="text-end">Do zapłaty</th><th>Status</th></tr>
            </thead>
            <tbody>
                <tr><td>Antoni Kowalski</td><td>Skrzaty U-9</td><td>Standard</td><td class="text-end">280 zł</td><td>—</td><td class="text-end"><strong>280 zł</strong></td><td><span class="badge bg-info">Do wygenerowania</span></td></tr>
                <tr><td>Maja Kowalska</td><td>Skrzaty U-11</td><td>Standard</td><td class="text-end">320 zł</td><td>Rodzeństwo -10%</td><td class="text-end"><strong>288 zł</strong></td><td><span class="badge bg-info">Do wygenerowania</span></td></tr>
                <tr><td>Filip Kowalewski</td><td>Skrzaty U-9</td><td>Standard</td><td class="text-end">280 zł</td><td>Stypendium -50%</td><td class="text-end"><strong>140 zł</strong></td><td><span class="badge bg-info">Do wygenerowania</span></td></tr>
                <tr><td>Cezary Nowak</td><td>Skrzaty U-9</td><td>Standard</td><td class="text-end">—</td><td>Wstrzymanie</td><td class="text-end"><strong>—</strong></td><td><span class="badge bg-warning text-dark">Pominięty (urlop)</span></td></tr>
            </tbody>
        </table>
        <small class="text-muted">… i 335 więcej. Sprawdź anomalie przed zatwierdzeniem.</small>
        <hr>
        <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-outline-secondary">Eksport preview CSV</button>
            <button class="btn btn-success">Zatwierdź i wygeneruj (339 faktur)</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: preview paczki faktur z anomaliami (rodzeństwo, stypendium, urlop).</div>
</div>

<h2>Faktura jednorazowa</h2>
<p>
    Czasem trzeba wystawić jednorazową fakturę (np. opłata startowa za turniej,
    sklep klubu, składka jednorazowa "wpisowe"). Wchodzisz w
    <em>Finanse → Faktury → Nowa faktura jednorazowa</em>, wybierasz członka,
    wpisujesz pozycje, kwoty, stawkę VAT. Tworzy się jak każda standardowa
    faktura.
</p>

<h2>Numeracja</h2>
<p>
    Numeracja faktur jest ciągła i zgodna z polskim formatem (np.
    <code>FV-2026/05/138</code>). Klub konfiguruje wzorzec w ustawieniach.
    ClubDesk nie pozwala na "dziury" w numeracji — jeśli skasujesz fakturę
    (przed wysłaniem), kolejny numer i tak idzie naprzód, a wycofana faktura
    zostaje z notatką "ANULOWANA". To wymóg ustawy o rachunkowości.
</p>

<h2>Ulgi i stypendia</h2>
<p>
    Ulgi konfiguruje zarząd klubu w <em>Finanse → Ulgi i stypendia</em>. Najczęstsze:
</p>
<ul>
    <li>Ulga rodzeństwa (−10% za drugie dziecko, −20% za trzecie).</li>
    <li>Stypendium socjalne (zniżka indywidualna).</li>
    <li>Wstrzymanie składki (urlop, kontuzja &gt;30 dni).</li>
</ul>
<p>
    Ulgi są stosowane automatycznie podczas generowania paczki — nie musisz
    ich pamiętać.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Po wygenerowaniu i wysłaniu fakturę można korygować <strong>tylko przez
    korektę (faktura korygująca)</strong>, nie przez edycję. To wymóg ustawowy
    — zobacz osobny rozdział o korektach.
</div>

<h2>Wysyłka e-mail</h2>
<p>
    Faktura jest wysyłana automatycznie na adres e-mail rodzica (lub samego
    członka — dla pełnoletnich) jako PDF z linkiem do płatności online (jeśli
    klub ma aktywną bramkę). Wzór wiadomości konfiguruje zarząd w
    <em>Ustawienia → Szablony e-mail</em>.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Po wygenerowaniu paczki sprawdź statystyki dostarczalności w <em>Finanse →
    Faktury → Statystyki wysyłki</em>. Jeśli &gt;5% wiadomości "bounce", coś
    jest nie tak ze skrzynką klubu — wtedy zgłoś to zarządowi.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
