<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Raport frekwencji (eksport CSV)</h1>
<p class="lead">
    Raport frekwencji to zbiorcze podsumowanie obecności w wybranym zakresie
    dat. Trenerzy używają go przed rozmowami rodzicielskimi, do podziału
    nagród za sezon i do uzasadniania składu na ważne turnieje.
</p>

<h2>Wejście w raport</h2>
<p>
    W menu lewym <em>Sekcje → konkretna sekcja → zakładka Statystyki</em>
    klikasz <strong>Raport frekwencji</strong>. Otwiera się ekran z trzema
    filtrami:
</p>
<ul>
    <li>Zakres dat (domyślnie: cały bieżący sezon).</li>
    <li>Typ zajęć (treningi / mecze / turnieje / wszystkie).</li>
    <li>Status (zaliczane / nie zaliczane do statystyki).</li>
</ul>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/sections/skrzaty-u9/attendance-report</div>
    <div class="manual-mockup-content">
        <h6>Raport frekwencji — Skrzaty (U-9)</h6>
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <label class="form-label small">Od</label>
                <input type="date" class="form-control form-control-sm" value="2025-09-01" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Do</label>
                <input type="date" class="form-control form-control-sm" value="2026-05-15" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Typ</label>
                <select class="form-select form-select-sm" disabled>
                    <option>Wszystkie zajęcia</option>
                </select>
            </div>
        </div>
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr><th>Zawodnik</th><th class="text-center">Obecny</th><th class="text-center">Spóźniony</th><th class="text-center">Nieobecny</th><th class="text-center">Zwolniony</th><th class="text-center">Frekwencja</th></tr>
            </thead>
            <tbody>
                <tr><td>Antoni Kowalski</td><td class="text-center">39</td><td class="text-center">1</td><td class="text-center">1</td><td class="text-center">0</td><td class="text-center"><span class="badge bg-success">96%</span></td></tr>
                <tr><td>Bartek Wójcik</td><td class="text-center">35</td><td class="text-center">3</td><td class="text-center">3</td><td class="text-center">0</td><td class="text-center"><span class="badge bg-success">88%</span></td></tr>
                <tr><td>Cezary Nowak</td><td class="text-center">22</td><td class="text-center">3</td><td class="text-center">16</td><td class="text-center">0</td><td class="text-center"><span class="badge bg-warning text-dark">61%</span></td></tr>
                <tr><td>Dawid Lewandowski</td><td class="text-center">37</td><td class="text-center">2</td><td class="text-center">2</td><td class="text-center">0</td><td class="text-center"><span class="badge bg-success">91%</span></td></tr>
                <tr><td>Emil Zieliński</td><td class="text-center">33</td><td class="text-center">2</td><td class="text-center">3</td><td class="text-center">3</td><td class="text-center"><span class="badge bg-success">84%</span></td></tr>
            </tbody>
            <tfoot class="table-light">
                <tr><td><strong>Średnia sekcji</strong></td><td colspan="4"></td><td class="text-center"><strong>84%</strong></td></tr>
            </tfoot>
        </table>
        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> Eksport CSV</button>
            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Eksport PDF</button>
            <button class="btn btn-outline-primary btn-sm"><i class="bi bi-envelope"></i> Wyślij do rodziców</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: raport frekwencji z opcjami eksportu CSV/PDF.</div>
</div>

<h2>Eksport CSV</h2>
<p>
    Eksport CSV generuje plik gotowy do otwarcia w Excelu lub Google Sheets.
    Kolumny: <code>Imię, Nazwisko, Rocznik, Obecny, Spóźniony, Nieobecny,
    Zwolniony, Frekwencja %</code>. Plik jest zakodowany jako UTF-8 z BOM, co
    pozwala na poprawne wyświetlanie polskich znaków w Excelu Windows.
</p>

<h2>Eksport PDF</h2>
<p>
    PDF zawiera logo klubu, nagłówek (sekcja, zakres dat, podpis trenera) oraz
    tabelę z frekwencją. Plik nadaje się do dołączenia do rocznego sprawozdania
    klubu albo do wydruku na zebraniu rodziców.
</p>

<h2>Wysyłka do rodziców</h2>
<p>
    Opcja <em>Wyślij do rodziców</em> generuje <strong>spersonalizowany e-mail</strong>
    dla każdego rodzica z frekwencją tylko jego dziecka — nie ujawnia danych
    innych zawodników. To bardzo dobre narzędzie na koniec sezonu albo przed
    rozmowami rodzicielskimi.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Frekwencja <strong>nie liczy</strong> okresów <em>wstrzymanej</em> obecności
    zawodnika (np. długa kontuzja). Dzięki temu zawodnik wracający po pół roku
    przerwy nie spada do "30%" tylko dlatego, że licznik dni jest globalny.
</div>

<h2>Filtr "tylko mecze i turnieje"</h2>
<p>
    Frekwencja na meczach to często ważniejszy wskaźnik niż na treningach — bo
    tu zawodnik powinien być, jeśli się zgłosił. Filtr <em>Typ → Mecze i
    turnieje</em> pokaże osobną statystykę i jest niezastąpiony do dyskusji o
    składach.
</p>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
