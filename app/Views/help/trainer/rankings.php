<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Ranking i statystyki zawodników</h1>
<p class="lead">
    Ranking zawodników to widok, w którym widzisz, jak Twoi podopieczni wypadają
    w sezonie — pod względem aktywności (frekwencja), efektywności (bramki,
    asysty), regularności i wagi turniejów, w których brali udział.
</p>

<h2>Sezonowe statystyki sekcji</h2>
<p>
    W karcie sekcji, w zakładce <strong>Statystyki</strong>, znajdziesz tabelę
    podsumowującą sezon. Kolumny są dynamiczne — zależą od dyscypliny — ale dla
    sportów drużynowych zwykle są to: minuty na boisku, bramki, asysty, kartki,
    średnia ocena meczu (jeśli sędziowie ją wystawiają).
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/sections/skrzaty-u9/stats</div>
    <div class="manual-mockup-content">
        <h6>Statystyki sezonu 2025/26 — Skrzaty (U-9)</h6>
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr><th>#</th><th>Zawodnik</th><th class="text-center">Mecze</th><th class="text-center">Min.</th><th class="text-center">Bramki</th><th class="text-center">Asysty</th><th class="text-center">Frekwencja</th><th class="text-center">Punkty</th></tr>
            </thead>
            <tbody>
                <tr><td>1</td><td><strong>Antoni Kowalski</strong></td><td class="text-center">12</td><td class="text-center">540</td><td class="text-center">9</td><td class="text-center">4</td><td class="text-center">96%</td><td class="text-center"><strong>87.4</strong></td></tr>
                <tr><td>2</td><td>Dawid Lewandowski</td><td class="text-center">11</td><td class="text-center">495</td><td class="text-center">6</td><td class="text-center">5</td><td class="text-center">91%</td><td class="text-center">72.1</td></tr>
                <tr><td>3</td><td>Bartek Wójcik</td><td class="text-center">12</td><td class="text-center">510</td><td class="text-center">4</td><td class="text-center">6</td><td class="text-center">88%</td><td class="text-center">68.9</td></tr>
                <tr><td>4</td><td>Emil Zieliński</td><td class="text-center">10</td><td class="text-center">420</td><td class="text-center">3</td><td class="text-center">3</td><td class="text-center">84%</td><td class="text-center">52.0</td></tr>
                <tr><td>5</td><td>Hubert Kowalik</td><td class="text-center">11</td><td class="text-center">460</td><td class="text-center">2</td><td class="text-center">2</td><td class="text-center">81%</td><td class="text-center">44.5</td></tr>
                <tr><td>6</td><td>Cezary Nowak</td><td class="text-center">7</td><td class="text-center">280</td><td class="text-center">1</td><td class="text-center">0</td><td class="text-center">61%</td><td class="text-center">22.0</td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: ranking sezonowy sekcji. Kolumna "Punkty" to zsumowany wynik z meczów i turniejów.</div>
</div>

<h2>Metryka "Punkty"</h2>
<p>
    Punkty to syntetyczny wskaźnik, którego formułę konfiguruje zarząd klubu w
    ustawieniach. Domyślny przelicznik dla sportów drużynowych:
</p>
<ul>
    <li>bramka — 10 pkt;</li>
    <li>asysta — 5 pkt;</li>
    <li>obecność na meczu — 1 pkt;</li>
    <li>kartka żółta — −2 pkt, czerwona — −5 pkt;</li>
    <li>obecność na treningu — 0,5 pkt.</li>
</ul>

<h2>Filtr "tylko mecze"</h2>
<p>
    Tabela domyślnie agreguje wszystkie wydarzenia — treningi i mecze. Możesz
    przełączyć na <em>"tylko mecze"</em> dla rzetelnej oceny rywalizacyjnej
    (frekwencja na treningach jest osobno, więc nie zniekształca wyniku
    najlepszych z treningu, a słabszych z meczów).
</p>

<h2>Ranking sezonowy klubu</h2>
<p>
    Klub może udostępnić również ranking <em>cross-sekcji</em> — np. "najlepsi
    strzelcy klubu". W menu <strong>Turnieje → Ranking klubu</strong> zobaczysz
    zestawienie wszystkich zawodników (we wszystkich sekcjach) wg dowolnej
    metryki.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Tabelę możesz wyeksportować do PDF z grafiką klubu — idealny materiał na
    zakończenie sezonu. Każdy zawodnik dostaje swój wiersz; medale automatyczne
    dla top-3 są opcją, którą włącza zarząd.
</div>

<h2>Indywidualna karta postępu</h2>
<p>
    Klikając imię zawodnika trafisz w jego osobistą kartę postępów — wykres
    bramek miesiąc po miesiącu, wykres frekwencji oraz wykres karier
    rywalizacyjnych (turnieje z medalami). To narzędzie używane na rozmowach
    rodzicielskich i okazałej karierze juniora.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Statystyki nie są transferowalne między sekcjami — przeniesienie zawodnika
    do innej grupy zachowuje historię, ale w nowej sekcji punktacja restartuje.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
