<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Aktualizacja danych członka</h1>
<p class="lead">
    Życie pisze własne scenariusze — rodziny się przeprowadzają, dzieci dostają
    nowe numery telefonów, kobiety zmieniają nazwiska. ClubDesk pozwala
    aktualizować dane członka w ciągu kilkunastu sekund, z pełnym audytem
    zmian dla RODO.
</p>

<h2>Wyszukanie członka</h2>
<p>
    Z lewego menu wchodzisz w <strong>Członkowie</strong>. Na górze listy masz
    pole wyszukiwania — wpisujesz fragment imienia/nazwiska/PESEL/numeru
    licencji. System filtruje na żywo. Klikasz wiersz — wchodzisz w pełną kartę
    członka.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/members</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between mb-2">
            <input type="search" class="form-control" placeholder="Szukaj: imię, nazwisko, PESEL, licencja…" value="kowal" style="max-width:300px;" disabled>
            <div>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> Filtry</button>
                <button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Nowy członek</button>
            </div>
        </div>
        <table class="table table-sm table-hover">
            <thead class="table-light">
                <tr><th>#</th><th>Imię i nazwisko</th><th>Rocznik</th><th>Sekcja</th><th>Status</th><th>Składki</th><th>Akcje</th></tr>
            </thead>
            <tbody>
                <tr><td>147</td><td><strong>Antoni Kowalski</strong></td><td>2016</td><td>Skrzaty U-9</td><td><span class="badge bg-success">aktywny</span></td><td><span class="badge bg-success">opłacone</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button></td></tr>
                <tr><td>148</td><td>Maja Kowalska</td><td>2014</td><td>Skrzaty U-11</td><td><span class="badge bg-success">aktywny</span></td><td><span class="badge bg-success">opłacone</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button></td></tr>
                <tr><td>149</td><td>Filip Kowalewski</td><td>2016</td><td>Skrzaty U-9</td><td><span class="badge bg-success">aktywny</span></td><td><span class="badge bg-warning text-dark">zaległe 220 zł</span></td><td><button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button></td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: lista członków z filtrem na żywo i kolumną szybkiej edycji.</div>
</div>

<h2>Edycja danych</h2>
<p>
    W karcie członka klikasz <strong>Edytuj</strong>. Pola są pogrupowane w
    sekcje: <em>Podstawowe</em>, <em>Adresowe</em>, <em>Kontaktowe</em>,
    <em>Rozliczeniowe</em>, <em>Specyficzne dla sekcji</em>. Każde pole, które
    zmienisz, zostaje podświetlone na żółto — przed zapisem widzisz różnice.
</p>

<h2>Niezmienne pola</h2>
<p>
    Niektóre pola są <strong>zablokowane</strong> w trybie zwykłej edycji:
</p>
<ul>
    <li><strong>PESEL</strong> — wymaga zatwierdzenia administratora.</li>
    <li><strong>Data urodzenia</strong> — j.w.</li>
    <li><strong>Numer licencji</strong> — generowany automatycznie, zmiana
        wymaga interwencji administratora i prześle informację do federacji.</li>
</ul>
<p>
    Jeżeli musisz zmienić PESEL (np. korekta błędu wprowadzonego przy
    rejestracji), klikasz "Poproś o korektę" — wniosek trafia do zarządu klubu.
</p>

<h2>Zmiana sekcji</h2>
<p>
    Przeniesienie zawodnika do innej sekcji ma dwa scenariusze:
</p>
<ul>
    <li><strong>Awans wewnętrzny</strong> (np. Skrzaty U-9 → Orliki U-11) — sekcja
        odpowiada wiekowi, więc operacja jest natychmiastowa. Trener docelowej
        sekcji dostaje notyfikację.</li>
    <li><strong>Zmiana dyscypliny</strong> (np. piłka → judo) — operacja
        wymaga akceptacji obu trenerów oraz pisemnej zgody rodzica.</li>
</ul>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Zmiana sekcji nie generuje korekt faktur — składki obowiązują nadal dla
    tego samego planu. Jeżeli zmiana wiąże się ze zmianą wysokości składki
    (np. judo droższe od piłki), korekta jest zadaniem księgowego, nie
    sekretariatu.
</div>

<h2>Audyt zmian</h2>
<p>
    Każda Twoja zmiana danych członka jest zapisywana w logu: kto zmienił, kiedy,
    z jakiej wartości na jaką. Log jest dostępny dla administratora klubu i jest
    wymagany w przypadku audytu RODO. Loga nie da się sfałszować ani usunąć.
</p>

<h2>Wypisanie / dezaktywacja</h2>
<p>
    Akcja <strong>"Zakończ członkostwo"</strong> dezaktywuje członka — nie usuwa
    danych, ale wyłącza generowanie kolejnych faktur i dostęp do treningów.
    Dane pozostają w bazie do upłynięcia okresu retencji (domyślnie 5 lat od
    ostatniej aktywności). Pełne usunięcie jest możliwe na wyraźne żądanie
    członka (RODO art. 17) i wymaga akceptacji administratora.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Jeżeli członek wraca po przerwie (np. po roku), nie tworzysz nowego konta —
    reaktywujesz dezaktywowane. Wszystkie historyczne dane (frekwencje, wyniki,
    medale) wracają wraz z nim.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
