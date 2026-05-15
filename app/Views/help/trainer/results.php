<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Wpisywanie wyników (perspektywa trenera)</h1>
<p class="lead">
    W większości turniejów to organizator (lub sędzia) wpisuje wyniki. Ale w
    sparingach, w lidze regionalnej z elektronicznym protokołem czy w turniejach
    własnych klubu — wpisuje je trener prowadzący drużynę. ClubDesk ma do tego
    osobny ekran, zaprojektowany tak, żeby dało się to robić "na bieżąco"
    podczas meczu.
</p>

<h2>Wejście w ekran wyników</h2>
<p>
    W karcie turnieju klikasz na zakładkę <strong>Drabinka i wyniki</strong>, a
    następnie wybierasz konkretny mecz — np. <em>Skrzaty CD vs UKS Bielsko 2</em>.
    Otwiera się formularz, w którym wpisujesz wynik końcowy, ewentualnie strzelców
    bramek, asystentów i karty.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/matches/bielsko-cup-2026/m12/score</div>
    <div class="manual-mockup-content">
        <h6>Mecz: Skrzaty CD vs UKS Bielsko 2 · 17.05.2026 11:30 · Grupa A</h6>
        <form>
            <div class="row g-3 align-items-end">
                <div class="col text-end">
                    <h4 class="mb-0">Skrzaty CD</h4>
                </div>
                <div class="col-auto">
                    <input type="number" class="form-control text-center" style="width:80px;font-size:1.5rem;" value="3" disabled>
                </div>
                <div class="col-auto fs-3">:</div>
                <div class="col-auto">
                    <input type="number" class="form-control text-center" style="width:80px;font-size:1.5rem;" value="1" disabled>
                </div>
                <div class="col">
                    <h4 class="mb-0">UKS Bielsko 2</h4>
                </div>
            </div>
            <hr>
            <h6>Bramki (Skrzaty CD)</h6>
            <table class="table table-sm">
                <thead class="table-light">
                    <tr><th style="width:80px;">Min.</th><th>Strzelec</th><th>Asysta</th><th>Typ</th></tr>
                </thead>
                <tbody>
                    <tr><td>11'</td><td>Antoni Kowalski</td><td>Bartek Wójcik</td><td>z gry</td></tr>
                    <tr><td>23'</td><td>Dawid Lewandowski</td><td>—</td><td>karny</td></tr>
                    <tr><td>34'</td><td>Antoni Kowalski</td><td>Emil Zieliński</td><td>z gry</td></tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus"></i> Dodaj bramkę</button>
            <hr>
            <h6>Kartki</h6>
            <div class="text-muted small">Brak kartek.</div>
            <hr>
            <div class="d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz i wyślij do organizatora</button>
                <button class="btn btn-outline-secondary">Zapisz roboczo</button>
            </div>
        </form>
    </div>
    <div class="manual-mockup-caption">Mockup: formularz wpisywania wyniku meczu — z bramkami, asystami i kartkami.</div>
</div>

<h2>Krok po kroku</h2>
<ol>
    <li>Wpisz wynik końcowy w dwóch polach numerycznych.</li>
    <li>Dla każdej bramki dodaj wiersz: minutę, strzelca (lista z Twojej drużyny),
        asystenta (opcjonalnie), typ (z gry / karny / samobój).</li>
    <li>Dla każdej kartki dodaj wiersz: zawodnik, minuta, kolor.</li>
    <li>Sprawdź sumę bramek — system pokaże ostrzeżenie, jeśli wpisanych bramek
        jest mniej niż wynik.</li>
    <li>Kliknij <strong>Zapisz i wyślij do organizatora</strong>.</li>
</ol>

<h2>Tryb roboczy</h2>
<p>
    W trakcie meczu możesz <em>Zapisać roboczo</em> — wynik nie idzie wtedy do
    organizatora, ale zostaje zapisany u Ciebie. Dzięki temu możesz uzupełniać
    bramki "na żywo" i wysłać dopiero po końcowym gwizdku.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Jeśli klub korzysta z <em>integracji federacyjnej</em> (np. PZPN
    Łączy nas piłka), zapisany wynik trafia automatycznie do systemu federacji.
    Zarząd klubu konfiguruje integrację — Ty tylko wpisujesz wynik.
</div>

<h2>Korekta wyniku</h2>
<p>
    Po wysłaniu wyniku jeszcze przez 24 godziny możesz go <strong>edytować</strong>
    (np. dopisać niezarejestrowanego asystenta). Po 24h wynik jest "zamrożony" —
    poprawki wymagają interwencji zarządu.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Wynik wpisuje trener tylko jednej strony — najczęściej trener-gospodarz lub
    sędzia. Jeżeli oba kluby próbują wpisać wynik, system zapisuje wersję
    pierwszą i blokuje drugą z komunikatem "wynik został już zapisany przez X".
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
