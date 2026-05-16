<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Dodanie zawodnika do sekcji</h1>
<p class="lead">
    Trener nie tworzy nowych członków klubu — robi to sekretariat. Trener
    natomiast może <strong>przypisać już zarejestrowanego członka</strong> do
    swojej sekcji albo poprosić sekretariat o stworzenie konta nowemu zawodnikowi.
</p>

<h2>Scenariusz 1: zawodnik już jest w klubie</h2>
<p>
    W karcie sekcji (zakładka <em>Zawodnicy</em>) klikasz <strong>+ Dodaj
    zawodnika</strong>. Pojawia się okno wyszukiwania — wpisujesz fragment imienia
    lub nazwiska, system pokaże listę członków klubu, którzy <em>nie są jeszcze</em>
    w Twojej sekcji. Wybierasz osobę, klikasz <em>Dodaj</em> i już — zawodnik
    pojawia się na liście.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/sections/skrzaty-u9/add-athlete</div>
    <div class="manual-mockup-content">
        <h6>Dodaj zawodnika do: Skrzaty (U-9)</h6>
        <div class="mb-3">
            <label class="form-label">Szukaj członka klubu</label>
            <input type="text" class="form-control" value="kow" disabled>
        </div>
        <div class="list-group mb-3">
            <a class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong>Antoni Kowalski</strong> · 2016 · członek od 2024-09-01</span>
                <span class="badge bg-info">w sekcji Orliki A</span>
            </a>
            <a class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong>Filip Kowalewski</strong> · 2016 · członek od 2025-02-14</span>
                <button class="btn btn-sm btn-primary">Dodaj</button>
            </a>
            <a class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong>Hubert Kowalik</strong> · 2017 · członek od 2025-09-01</span>
                <button class="btn btn-sm btn-primary">Dodaj</button>
            </a>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" disabled>
            <label class="form-check-label small">Pozostaw w poprzedniej sekcji (zawodnik w dwóch grupach)</label>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: wyszukiwarka członków klubu w widoku dodawania do sekcji.</div>
</div>

<h2>Scenariusz 2: zawodnik dopiero przychodzi</h2>
<p>
    Gdy na trening przychodzi <strong>nowa osoba</strong> bez konta w ClubDesk:
</p>
<ol>
    <li>Notujesz dane na kartce (lub w aplikacji w sekcji "Wniosek zapisania").</li>
    <li>Przekazujesz wniosek do sekretariatu — przyciskiem
        <em>Zaproponuj nowego członka</em> wysyłasz formularz z imieniem, datą
        urodzenia, kontaktem do rodzica.</li>
    <li>Sekretariat sprawdza dokumenty (zgoda rodzica, RODO, ewentualnie
        badania) i zakłada konto.</li>
    <li>Po założeniu konta nowy zawodnik pojawia się automatycznie w Twojej
        sekcji (na podstawie wskazanej w formularzu).</li>
</ol>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Nie pozwól trenować dziecku, dla którego nie ma jeszcze zgody rodzica i
    zaświadczenia lekarskiego. Polskie prawo sportowe traktuje to jako wykroczenie
    organizatora. ClubDesk pokazuje takich zawodników z czerwoną ikoną — nie da
    się zaznaczyć ich obecności, dopóki sekretariat nie uzupełni dokumentów.
</div>

<h2>Przenoszenie między sekcjami</h2>
<p>
    Gdy zawodnik kończy określony rocznik i przechodzi do starszej grupy,
    przenosisz go z poziomu listy <em>akcją <i class="bi bi-arrow-left-right"></i>
    Przenieś</em>. Operacja wymaga akceptacji trenera docelowej sekcji — system
    automatycznie wyśle mu powiadomienie.
</p>

<h2>Usunięcie z sekcji</h2>
<p>
    Akcja <em>Usuń z sekcji</em> oznacza tylko wypisanie zawodnika z grupy
    treningowej — <strong>nie usuwa go z klubu</strong>. Jego historia obecności
    i wyników pozostaje nietknięta. Pełne usunięcie członka z klubu jest możliwe
    wyłącznie z poziomu sekretariatu, po spełnieniu zasad retencji RODO.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Jeśli zawodnik jest "na zawieszeniu" (np. urlop, kontuzja), nie usuwaj go z
    sekcji — zamiast tego użyj akcji <em>Wstrzymaj</em>. Statystyki frekwencji
    nie będą obejmować okresu wstrzymania.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
