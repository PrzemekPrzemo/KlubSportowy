<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Jak działa system prowizji trenera</h1>
<p class="lead">
    Prowizje trenerów w ClubDesk są w pełni zautomatyzowane — w 90% klubów
    trener nie wystawia żadnych dokumentów, system sam wylicza należność i
    generuje raport, na podstawie którego księgowość robi przelew.
</p>

<h2>Reguły prowizji (commission rules)</h2>
<p>
    Każdy klub definiuje własne reguły wyliczania prowizji. Najczęściej spotykane
    schematy to:
</p>
<ul>
    <li><strong>Stałe wynagrodzenie miesięczne</strong> — niezależne od liczby
        treningów (np. 1500 zł/msc).</li>
    <li><strong>Stawka godzinowa</strong> — np. 80 zł za każdą godzinę
        zaprowadzonego treningu.</li>
    <li><strong>Procent od składek</strong> — np. 15% sumy składek członkowskich
        w sekcji w danym miesiącu.</li>
    <li><strong>Stawka stała + bonus frekwencyjny</strong> — 1000 zł + 10 zł
        za każdą obecność powyżej 75% frekwencji sekcji.</li>
    <li><strong>Bonusy turniejowe</strong> — np. 200 zł za drugie miejsce w
        turnieju rangi krajowej.</li>
</ul>

<h2>Hierarchia reguł</h2>
<p>
    Reguły mogą być nadane na trzech poziomach:
</p>
<ol>
    <li><strong>Globalna reguła klubu</strong> — np. "wszyscy trenerzy mają
        stawkę godzinową 80 zł".</li>
    <li><strong>Reguła sekcji</strong> — np. "sekcja Junior ma stawkę 100 zł/h".</li>
    <li><strong>Indywidualna reguła trenera</strong> — wynegocjowana, np. dla
        Ciebie 95 zł/h od września.</li>
</ol>
<p>
    Reguła indywidualna ma najwyższy priorytet. Jeśli nie ma indywidualnej —
    obowiązuje sekcyjna; jeśli nie ma sekcyjnej — klubowa.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/profile/commission</div>
    <div class="manual-mockup-content">
        <h6>Moje reguły prowizji</h6>
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr><th>Sekcja</th><th>Typ reguły</th><th>Stawka</th><th>Bonusy</th><th>Aktywna od</th></tr>
            </thead>
            <tbody>
                <tr><td>Skrzaty (U-9)</td><td>Godzinowa indywidualna</td><td>95 zł / h</td><td>frekwencja ≥80%: +10%</td><td>2025-09-01</td></tr>
                <tr><td>Orliki A (U-11)</td><td>Godzinowa sekcyjna</td><td>90 zł / h</td><td>—</td><td>2025-09-01</td></tr>
                <tr><td>Junior (U-15)</td><td>Stała miesięczna</td><td>1500 zł / msc</td><td>turniej krajowy 1-3 miejsce: +300 zł</td><td>2025-09-01</td></tr>
                <tr><td>Młodzik (asyst.)</td><td>Asysta — % stawki głównej</td><td>40% (= 36 zł/h)</td><td>—</td><td>2025-09-01</td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: reguły prowizji widoczne w profilu trenera. Tylko podgląd — modyfikacji dokonuje zarząd.</div>
</div>

<h2>Co liczy się jako "trening"</h2>
<p>
    Tylko <strong>zamknięte sesje</strong> z zaznaczoną obecnością. Jeżeli
    zapomnisz zamknąć trening, prowizja za niego nie zostanie naliczona w danym
    okresie. Dlatego zawsze klikaj <em>"Zakończ trening"</em> po obecności.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Trening, którego nie poprowadziłeś osobiście (przekazałeś zastępcy z opcją
    "oddaję zastępcy"), nie jest liczony Ci do prowizji. Jeżeli przekazałeś z
    opcją "zachowuję", trening jest liczony Tobie — niezależnie od tego, kto
    fizycznie go poprowadził.
</div>

<h2>Bonusy</h2>
<p>
    Bonusy są wyliczane raz w miesiącu — w noc z ostatniego dnia miesiąca na
    pierwszy następnego. Algorytm patrzy na konfigurację reguły i sumuje
    spełnione warunki (np. "frekwencja Twojej sekcji była 87% — należy się
    bonus 10%"). Jeżeli warunek był spełniony tylko częściowo (frekwencja 79%
    przy progu 80%), bonusu nie ma — system nie interpoluje.
</p>

<h2>Kto może modyfikować reguły</h2>
<p>
    Zmianę reguły może dokonać tylko <strong>zarząd klubu</strong> lub
    <strong>księgowy</strong> z odpowiednimi uprawnieniami. Trener ma <em>tylko
    podgląd</em>. Każda zmiana reguły jest logowana, a Ty dostajesz powiadomienie.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Jeżeli widzisz w raporcie zaskakującą kwotę, najpierw sprawdź reguły swojej
    sekcji w tej zakładce — czasem zmiana stawki w trakcie miesiąca powoduje
    "dziwne" liczby, które są w pełni poprawne.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
