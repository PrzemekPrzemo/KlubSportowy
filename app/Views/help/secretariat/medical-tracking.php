<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Badania medyczne — śledzenie ważności</h1>
<p class="lead">
    Polskie prawo wymaga, by każdy zawodnik sportowy posiadał aktualne
    zaświadczenie lekarskie (potocznie: "badania sportowe"). Ważność to typowo
    12 miesięcy dla osób &lt;23 lat i 24 miesiące dla starszych. Sekretariat jest
    odpowiedzialny za to, by każdy aktywny zawodnik miał ważne badania —
    ClubDesk wspiera ten proces alertami i raportami.
</p>

<h2>Lista zawodników z badaniami</h2>
<p>
    W menu <strong>Compliance → Badania medyczne</strong> widzisz pełną listę
    członków z aktywnymi badaniami. Kolumny:
</p>
<ul>
    <li>imię, nazwisko, rocznik, sekcja;</li>
    <li>data ostatniego badania, data wygaśnięcia, status;</li>
    <li>typ badania (sportowe, kardiologiczne, ortopedyczne);</li>
    <li>plik (PDF zaświadczenia).</li>
</ul>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/compliance/medical</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="mb-0">Badania medyczne — przegląd</h6>
            <div>
                <select class="form-select form-select-sm d-inline-block" style="width:auto;" disabled>
                    <option>Wszyscy aktywni</option>
                    <option selected>Wygasające &lt;30 dni</option>
                    <option>Wygasłe</option>
                </select>
            </div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-3"><div class="card bg-success-subtle"><div class="card-body p-2 text-center"><small>Aktualne</small><div class="h5 mb-0">317</div></div></div></div>
            <div class="col-md-3"><div class="card bg-warning-subtle"><div class="card-body p-2 text-center"><small>Wygasają &lt;30 dni</small><div class="h5 mb-0">22</div></div></div></div>
            <div class="col-md-3"><div class="card bg-danger-subtle"><div class="card-body p-2 text-center"><small>Wygasłe</small><div class="h5 mb-0">8</div></div></div></div>
            <div class="col-md-3"><div class="card bg-secondary-subtle"><div class="card-body p-2 text-center"><small>Brak ankietowanych</small><div class="h5 mb-0">3</div></div></div></div>
        </div>
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr><th>Członek</th><th>Sekcja</th><th>Ostatnie badanie</th><th>Wygasa</th><th>Dni</th><th>Status</th></tr>
            </thead>
            <tbody>
                <tr><td>Dawid Lewandowski</td><td>Skrzaty U-9</td><td>2025-05-30</td><td><strong>2026-05-30</strong></td><td><span class="text-warning">15</span></td><td><span class="badge bg-warning text-dark">Wygasa wkrótce</span></td></tr>
                <tr><td>Bartek Wójcik</td><td>Skrzaty U-9</td><td>2025-07-04</td><td>2026-07-04</td><td class="text-warning">50</td><td><span class="badge bg-warning text-dark">Wygasa wkrótce</span></td></tr>
                <tr><td>Cezary Nowak</td><td>Skrzaty U-9</td><td>2025-03-15</td><td><strong>2026-03-15</strong></td><td><span class="text-danger">−62</span></td><td><span class="badge bg-danger">Wygasłe!</span></td></tr>
                <tr><td>Iza Pawlak</td><td>Skrzaty U-9</td><td>—</td><td>—</td><td>—</td><td><span class="badge bg-secondary">Brak badań</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: przegląd badań z filtrem i alertami "wygasa wkrótce".</div>
</div>

<h2>Cztery statusy badań</h2>
<ul>
    <li><strong>Aktualne</strong> — termin ważności co najmniej 30 dni do przodu.</li>
    <li><strong>Wygasa wkrótce</strong> — &lt;30 dni do wygaśnięcia.</li>
    <li><strong>Wygasłe</strong> — termin minął. Zawodnik nie powinien trenować.</li>
    <li><strong>Brak badań</strong> — nigdy nie wprowadzono badania (nowy
        członek, jeszcze w fazie rejestracji).</li>
</ul>

<h2>Automatyczne alerty</h2>
<p>
    ClubDesk wysyła automatyczne powiadomienia:
</p>
<ul>
    <li><strong>30 dni przed</strong> — e-mail do rodzica + push do aplikacji.</li>
    <li><strong>14 dni przed</strong> — drugi e-mail + push.</li>
    <li><strong>7 dni przed</strong> — SMS (jeżeli rodzic ma zarejestrowany numer).</li>
    <li><strong>W dniu wygaśnięcia</strong> — e-mail + status zawodnika zmieniony na "wygasłe".</li>
</ul>

<h2>Wprowadzanie nowego badania</h2>
<p>
    Kiedy rodzic przynosi zaświadczenie lekarskie:
</p>
<ol>
    <li>Skanujesz lub fotografujesz dokument (smartfon wystarczy).</li>
    <li>W karcie członka → Badania → <strong>+ Nowe badanie</strong>.</li>
    <li>Wpisujesz datę badania, typ (sportowe / kardio / ortopedyczne),
        datę wygaśnięcia (system proponuje +12 mc lub +24 mc).</li>
    <li>Wgrywasz PDF/zdjęcie.</li>
    <li>Klikasz <em>Zapisz</em> — status zawodnika zmienia się na "Aktualne".</li>
</ol>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Trener nie może zaznaczyć obecności zawodnika z wygasłymi badaniami.
    System pokaże komunikat blokujący. To zabezpieczenie prawne — w razie
    kontuzji bez ważnych badań klub może odpowiadać.
</div>

<h2>Raport "Lista do telefonu"</h2>
<p>
    Raz w tygodniu warto wygenerować raport "rodzice z dziećmi wygasającymi w
    ciągu 30 dni" — to pomaga aktywnie kontaktować się i nakłaniać do umówienia
    wizyty u lekarza. Raport jest w <em>Badania → Raporty → Lista do telefonu</em>.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Niektóre kluby organizują <em>"dzień badań"</em> — zapraszają lekarza
    sportowego, który w jednym dniu robi badania całej drużynie. To znacznie
    obniża wskaźnik "wygasłych" w długim okresie.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
