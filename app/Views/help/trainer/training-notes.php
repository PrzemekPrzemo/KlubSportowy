<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Notatki z treningu (dziennik trenera)</h1>
<p class="lead">
    Dziennik trenera to elektroniczny zeszyt, w którym zapisujesz, co robiliście
    na danym treningu. ClubDesk pozwala notować plan treningu, podsumowanie i
    indywidualne uwagi o zawodnikach — wszystko w jednym ekranie.
</p>

<h2>Plan treningu (przed)</h2>
<p>
    Plan zapisujesz najczęściej dzień wcześniej, np. wieczorem. Wchodzisz w
    konkretne wystąpienie treningu w kalendarzu i w sekcji <em>Plan</em>
    notujesz, co planujesz: rozgrzewka, główna część, taktyka, gra końcowa. Plan
    jest widoczny dla Twojego asystenta i dla zarządu — pomaga zachować ciągłość
    metodyczną.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/sessions/2026-05-13-skrzaty/plan</div>
    <div class="manual-mockup-content">
        <h6>Plan treningu — Skrzaty (U-9) · Pn 13.05 17:00</h6>
        <div class="mb-3">
            <label class="form-label small text-muted">Cel jednostki</label>
            <input type="text" class="form-control" value="Prowadzenie piłki — głowa nad piłką" disabled>
        </div>
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr><th style="width:60px;">Czas</th><th>Element</th><th>Uwagi</th></tr>
            </thead>
            <tbody>
                <tr><td>15'</td><td>Rozgrzewka (slalomy, dynamika)</td><td>3 stacje, 5 min każda</td></tr>
                <tr><td>20'</td><td>Prowadzenie piłki w slalomie</td><td>najpierw wolno, potem narastająco</td></tr>
                <tr><td>15'</td><td>Mini-gra 3v3 z bramkami strefowymi</td><td>uwaga: zmiana stron co 3 min</td></tr>
                <tr><td>10'</td><td>Cool-down i podsumowanie</td><td>pochwała najlepszych za "głowę nad piłką"</td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: plan treningu jako tabela czasowa.</div>
</div>

<h2>Podsumowanie (po treningu)</h2>
<p>
    Po treningu, jeszcze w szatni lub po drodze do domu, otwierasz tę samą sesję
    i przechodzisz do sekcji <em>Podsumowanie</em>. Zapisujesz, co poszło dobrze
    i co wymaga poprawy. Pole ma 1000 znaków — ma być zwięzłe, nie esej.
</p>

<h2>Notatki indywidualne</h2>
<p>
    W tym samym ekranie obok każdego obecnego zawodnika masz ikonkę ołówka,
    która otwiera <strong>notkę personalną</strong>. To miejsce na obserwacje
    rozwojowe — np. "Antek dziś świetnie utrzymywał głowę nad piłką, podkreślić
    przy rodzicu". Notatki personalne agregują się w profil zawodnika (sekcja
    <em>Postępy</em>).
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Spójny dziennik trenera to ogromna wartość przy długofalowej pracy z
    zawodnikiem — po 2 sezonach zobaczysz w jego profilu listę 80–100 wpisów,
    z których możesz wyciągnąć obraz rozwoju. Dla rodzica taka karta jest też
    elementem rozmowy o postępach.
</div>

<h2>Załączniki</h2>
<p>
    Do każdej jednostki możesz dołączyć pliki — najczęściej PDF z planem
    skopiowanym z metodyki klubu albo zdjęcie diagramu narysowanego na tablicy.
    Limit jednego pliku to 5 MB, łącznie sesja może mieć do 20 MB załączników.
</p>

<h2>Eksport dziennika</h2>
<p>
    Jeżeli zarząd klubu wymaga papierowego (lub PDF-owego) dziennika trenerskiego
    na koniec sezonu, możesz wygenerować raport jednym kliknięciem: <em>Moje
    sekcje → wybierz sekcję → Eksport dziennika</em>. Plik zawiera komplet
    treningów sekcji w wybranym zakresie dat z planem i podsumowaniem.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Notatki <em>publiczne</em> (plan, podsumowanie) widzą inni trenerzy i zarząd.
    Notatki <em>personalne</em> tylko Ty. Uważaj, gdzie wpisujesz uwagi o
    wrażliwym charakterze (zdrowie, rodzina).
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
