<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Moje sekcje i zawodnicy</h1>
<p class="lead">
    Sekcja w ClubDesk to grupa treningowa — zbiór zawodników, którzy regularnie
    ćwiczą razem. Przykłady sekcji w klubie piłkarskim: <em>Skrzaty U-9</em>,
    <em>Orliki U-11</em>, <em>Junior U-15</em>. Trener może opiekować się jedną
    sekcją lub wieloma — wszystko zależy od jego umowy z klubem.
</p>

<h2>Otwarcie listy sekcji</h2>
<p>
    W lewym menu klikasz pozycję <strong>Sekcje</strong>. Zobaczysz listę wszystkich
    grup, do których zostałeś przypisany. Każdy wiersz pokazuje: nazwę sekcji,
    kategorię wiekową, liczbę zawodników, dzień i godzinę treningu, status
    (aktywna / wstrzymana) oraz Twoją rolę (główny trener / asystent).
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/sections</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between mb-2">
            <h6 class="mb-0">Moje sekcje (4)</h6>
            <div>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> Filtry</button>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Eksport</button>
            </div>
        </div>
        <table class="table table-sm table-hover">
            <thead class="table-light">
                <tr><th>Sekcja</th><th>Kategoria</th><th class="text-center">Zawodnicy</th><th>Trening</th><th>Rola</th><th>Status</th></tr>
            </thead>
            <tbody>
                <tr><td><strong>Skrzaty</strong></td><td>U-9</td><td class="text-center">14</td><td>Pn, Śr 17:00</td><td>Główny</td><td><span class="badge bg-success">Aktywna</span></td></tr>
                <tr><td><strong>Orliki A</strong></td><td>U-11</td><td class="text-center">18</td><td>Wt, Czw 17:30</td><td>Główny</td><td><span class="badge bg-success">Aktywna</span></td></tr>
                <tr><td><strong>Młodzik</strong></td><td>U-13</td><td class="text-center">16</td><td>Pn, Pt 18:30</td><td>Asystent</td><td><span class="badge bg-success">Aktywna</span></td></tr>
                <tr><td><strong>Junior</strong></td><td>U-15</td><td class="text-center">14</td><td>Wt, Pt 19:00</td><td>Główny</td><td><span class="badge bg-warning text-dark">Pauza letnia</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: lista sekcji trenera. Pauzowanie sekcji zostawia historię obecności, ale wyłącza nowe zgłoszenia.</div>
</div>

<h2>Otwarcie pojedynczej sekcji</h2>
<p>
    Klikasz wiersz sekcji — otwiera się jej karta z trzema zakładkami:
    <strong>Zawodnicy</strong>, <strong>Harmonogram</strong> i <strong>Statystyki</strong>.
    Pierwsza zakładka pokazuje listę wszystkich zawodników grupy z podstawowymi
    informacjami i kolumną akcji.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/sections/skrzaty-u9</div>
    <div class="manual-mockup-content">
        <h6>Skrzaty (U-9) — 14 zawodników</h6>
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link active">Zawodnicy</a></li>
            <li class="nav-item"><a class="nav-link">Harmonogram</a></li>
            <li class="nav-item"><a class="nav-link">Statystyki</a></li>
        </ul>
        <table class="table table-sm table-hover">
            <thead class="table-light">
                <tr><th>#</th><th>Zawodnik</th><th>Rocznik</th><th>Frekwencja</th><th>Badania</th><th>Akcje</th></tr>
            </thead>
            <tbody>
                <tr><td>1</td><td>Antoni Kowalski</td><td>2016</td><td><span class="badge bg-success">96%</span></td><td>do 2026-09-12</td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button></td></tr>
                <tr><td>2</td><td>Bartek Wójcik</td><td>2015</td><td><span class="badge bg-success">88%</span></td><td>do 2026-07-04</td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button></td></tr>
                <tr><td>3</td><td>Cezary Nowak</td><td>2016</td><td><span class="badge bg-warning text-dark">61%</span></td><td><span class="text-danger">wygasły</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button></td></tr>
                <tr><td>4</td><td>Dawid Lewandowski</td><td>2015</td><td><span class="badge bg-success">91%</span></td><td>do 2026-05-30</td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button></td></tr>
                <tr><td>5</td><td>Emil Zieliński</td><td>2016</td><td><span class="badge bg-success">84%</span></td><td>do 2027-01-12</td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button></td></tr>
            </tbody>
        </table>
        <small class="text-muted">… i 9 kolejnych</small>
    </div>
    <div class="manual-mockup-caption">Mockup: karta sekcji z listą zawodników. Kolumny: nr, imię, rocznik, frekwencja sezonowa, status badań.</div>
</div>

<h2>Sortowanie i filtry</h2>
<p>
    Domyślnie lista jest posortowana alfabetycznie po nazwisku. Możesz przełączyć
    się na sortowanie po frekwencji (przydatne, gdy chcesz wytypować zawodników
    do nagród za obecność) lub po dacie wygaśnięcia badań (kto pilnie potrzebuje
    badań).
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Kolumna <em>Badania</em> miga na czerwono, gdy do wygaśnięcia zostało mniej
    niż 30 dni. Zawodnik z wygasłymi badaniami nie powinien być dopuszczony do
    treningu — ClubDesk wyświetli ostrzeżenie przy próbie zaznaczenia obecności.
</div>

<h2>Eksport listy</h2>
<p>
    Przyciskiem <em>Eksport</em> ściągniesz listę w formacie CSV lub PDF — często
    przydaje się na turnieje, gdy organizator wymaga papierowej listy startowej
    z numerami licencji.
</p>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
