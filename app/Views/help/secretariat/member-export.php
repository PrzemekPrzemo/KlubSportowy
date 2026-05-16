<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Eksport listy członków</h1>
<p class="lead">
    Sekretariat regularnie przygotowuje wyciągi z listy członków — do zarządu,
    do urzędu skarbowego, do federacji sportowej, do firm ubezpieczeniowych.
    ClubDesk pozwala wyeksportować dane w trzech formatach (CSV, XLSX, PDF) z
    pełną kontrolą zakresu danych.
</p>

<h2>Otwarcie kreatora eksportu</h2>
<p>
    Na liście członków klikasz <strong>Eksport</strong>. Otwiera się 3-stopniowy
    kreator: zakres → kolumny → format.
</p>

<h2>Krok 1: zakres</h2>
<p>
    Wybierasz, których członków eksportować:
</p>
<ul>
    <li><strong>Wszystkich</strong> — pełna baza.</li>
    <li><strong>Aktywnych</strong> — tylko z aktywnym członkostwem.</li>
    <li><strong>Z wybranej sekcji</strong> — np. tylko Skrzaty U-9.</li>
    <li><strong>Spełniających filtr</strong> — używasz aktywnego filtra z listy
        (np. "rocznik 2014–2016, sekcja Skrzaty, składki opłacone").</li>
</ul>

<h2>Krok 2: kolumny</h2>
<p>
    Zaznaczasz, które dane chcesz w eksporcie. Domyślnie wybrane są kolumny
    podstawowe (imię, nazwisko, rocznik, sekcja). Możesz dodać kolumny
    "wrażliwe" (PESEL, adres, kontakt) — ale tylko jeśli masz odpowiednie
    uprawnienia. Każde dodanie kolumny wrażliwej jest logowane.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/members/export?step=2</div>
    <div class="manual-mockup-content">
        <h6>Eksport listy członków — krok 2/3: Kolumny</h6>
        <div class="row g-2">
            <div class="col-md-6">
                <h6 class="small text-muted">Podstawowe</h6>
                <div class="form-check"><input type="checkbox" class="form-check-input" checked disabled><label class="form-check-label">Imię</label></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" checked disabled><label class="form-check-label">Nazwisko</label></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" checked disabled><label class="form-check-label">Rocznik</label></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" checked disabled><label class="form-check-label">Sekcja</label></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">Numer licencji</label></div>
            </div>
            <div class="col-md-6">
                <h6 class="small text-muted">Wrażliwe <span class="badge bg-warning text-dark small">audyt</span></h6>
                <div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">PESEL</label></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">Adres zamieszkania</label></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">Telefon</label></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">E-mail</label></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">Wymagane: cel eksportu (poniżej)</label></div>
            </div>
        </div>
        <div class="mt-3">
            <label class="form-label small">Cel eksportu (wymagane dla danych wrażliwych)</label>
            <input type="text" class="form-control" value="Wniosek do PZPN o przyznanie licencji młodzieżowych — sezon 2026/27" disabled>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: kreator eksportu — wybór kolumn z rozróżnieniem na wrażliwe.</div>
</div>

<h2>Krok 3: format i akcja</h2>
<p>
    Wybierasz format:
</p>
<ul>
    <li><strong>CSV</strong> — uniwersalny, otwiera się w Excelu i Google Sheets.</li>
    <li><strong>XLSX</strong> — Excel natywnie, z formatowaniem i obramowaniem.</li>
    <li><strong>PDF</strong> — do druku, z logo klubu, ułożenie A4 poziome.</li>
</ul>
<p>
    Po kliknięciu <em>Generuj</em> plik tworzy się w tle. Dla małych eksportów
    (do 200 osób) jest gotowy natychmiast. Dla większych — dostajesz e-mail z
    linkiem do pobrania po 2–3 minutach.
</p>

<h2>Eksporty masowe</h2>
<p>
    Eksporty powyżej 1000 rekordów wymagają zatwierdzenia kierownika
    sekretariatu — kierownik klika <em>"Zaakceptuj eksport"</em> i Ty
    dostajesz powiadomienie, że plik jest gotowy. To zabezpieczenie przed
    masowym wyciekiem danych.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga RODO:</strong>
    Eksport danych osobowych musi mieć udokumentowany cel ("Wniosek o licencje
    do PZPN", "Lista uczestników na ubezpieczenie ZUS"). Pole "Cel eksportu" jest
    obowiązkowe przy włączeniu kolumn wrażliwych. Eksport bez celu jest
    zablokowany.
</div>

<h2>Logi eksportów</h2>
<p>
    Wszystkie eksporty (sukcesywne i nieudane) są zapisywane w <em>Administracja
    → Logi eksportów</em>. W razie audytu klub może udokumentować, kto, kiedy
    i w jakim celu pobrał jakie dane. To wymóg RODO art. 30 (rejestr czynności).
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Jeżeli regularnie eksportujesz ten sam zestaw kolumn (np. raz w miesiącu
    do skarbowego), zapisz go jako <em>"Szablon eksportu"</em>. Następnym razem
    wystarczy jedno kliknięcie i zmienisz tylko zakres.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
