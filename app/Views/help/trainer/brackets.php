<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Drabinka turniejowa — odczyt</h1>
<p class="lead">
    Drabinkę turniejową tworzy organizator (lub zarząd klubu, dla turniejów
    własnych) — trener jest jej <em>odbiorcą</em>, nie autorem. Ten ekran służy
    Ci do podglądu, planowania zmian i analizy rywali.
</p>

<h2>Widok pierwszej fazy</h2>
<p>
    W większości turniejów dla dzieci/młodzieży pierwsza faza to <strong>grupowa</strong>
    (każdy z każdym), a po niej "play-off" (drabinka eliminacyjna). ClubDesk
    wyświetla obie fazy: na górze tabele grup z wynikami, na dole drzewko
    pucharowe.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/tournaments/bielsko-cup-2026/bracket</div>
    <div class="manual-mockup-content">
        <h6>Bielsko Cup 2026 — Drabinka</h6>
        <h6 class="mt-3 small text-muted">Faza grupowa</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th colspan="6">Grupa A</th></tr><tr><th></th><th>Mecze</th><th>W</th><th>R</th><th>P</th><th>Pkt</th></tr></thead>
                    <tbody>
                        <tr><td><strong>Skrzaty CD</strong></td><td>2</td><td>2</td><td>0</td><td>0</td><td><strong>6</strong></td></tr>
                        <tr><td>UKS Bielsko 2</td><td>2</td><td>1</td><td>0</td><td>1</td><td>3</td></tr>
                        <tr><td>MKS Cieszyn</td><td>2</td><td>0</td><td>0</td><td>2</td><td>0</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th colspan="6">Grupa B</th></tr><tr><th></th><th>Mecze</th><th>W</th><th>R</th><th>P</th><th>Pkt</th></tr></thead>
                    <tbody>
                        <tr><td>Akademia Krakowska</td><td>2</td><td>2</td><td>0</td><td>0</td><td><strong>6</strong></td></tr>
                        <tr><td>UKS Skawina</td><td>2</td><td>1</td><td>0</td><td>1</td><td>3</td></tr>
                        <tr><td>Hej Bochnia</td><td>2</td><td>0</td><td>0</td><td>2</td><td>0</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <h6 class="mt-4 small text-muted">Play-off</h6>
        <div class="row g-3 text-center">
            <div class="col-3 align-self-center">
                <div class="border rounded p-2 mb-2 bg-light"><strong>Skrzaty CD</strong> 3</div>
                <div class="border rounded p-2 bg-light">Akademia Krakowska 1</div>
                <small class="text-muted">Półfinał 1</small>
            </div>
            <div class="col-3 align-self-center">
                <div class="border rounded p-2 bg-white"><strong>Skrzaty CD</strong></div>
                <small class="text-muted">Finał</small>
            </div>
            <div class="col-3 align-self-center">
                <div class="border rounded p-2 bg-white">?</div>
                <small class="text-muted">Mistrz</small>
            </div>
            <div class="col-3 align-self-center">
                <div class="border rounded p-2 mb-2 bg-light">UKS Bielsko 2 0</div>
                <div class="border rounded p-2 bg-light"><strong>UKS Skawina</strong> 2</div>
                <small class="text-muted">Półfinał 2</small>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: drabinka turnieju (tabele grupowe + drzewko play-off).</div>
</div>

<h2>Klikalne mecze</h2>
<p>
    Każdy mecz w drabince jest klikalny — przeniesie Cię do karty meczu, gdzie
    zobaczysz wynik, strzelców, kartki oraz <strong>listę zawodników, którzy
    wystąpili</strong> (Twojej drużyny i przeciwnika, jeśli udostępniono).
</p>

<h2>Analiza rywali</h2>
<p>
    Przed kolejnym meczem warto kliknąć w nazwę przeciwnika — zobaczysz jego
    ostatnie 5 meczów, najczęstszych strzelców i statystyki kartek. Świetne
    przygotowanie dla starszych kategorii (od U-13).
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Drabinkę możesz wyeksportować jako PNG lub PDF (przycisk w prawym górnym
    rogu). Zdjęcie idealnie nadaje się do wrzucenia na fanpage klubu po
    zakończeniu turnieju.
</div>

<h2>Aktualizacje live</h2>
<p>
    Drabinka odświeża się automatycznie co 30 sekund (jeśli masz włączony tryb
    "live"). Wyniki innych meczów pojawią się bez konieczności odświeżania
    strony — przydatne, gdy czekasz na rezultat z innej grupy, żeby wiedzieć,
    z kim grasz dalej.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Czas serwerowy turnieju jest publikowany przez organizatora. Jeżeli widzisz
    "fantomowy" wynik (np. 50:0), oznacza to walkower — przeciwnik nie zgłosił
    drużyny. Zarejestrowany jest jako "WO" w protokole.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
