<?php /** sport / attendance */ ?>
<p class="lead">Obecność może zaznaczać trener prowadzący, ale administrator klubu ma uprawnienia do edycji obecności na każdym treningu — np. gdy trener zapomni, jest na zwolnieniu lub gdy korygujesz historyczne dane.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Otwórz <strong>Sport → Obecność</strong> lub wejdź w konkretną sesję z kalendarza.</li>
    <li>Wybierz sekcję i datę treningu (domyślnie: dzisiejszy).</li>
    <li>Lista zawodników wyświetli się jako grid — kliknij ikonę przy nazwisku aby przełączyć status: ✓ obecny, ✗ nieobecny, U usprawiedliwiony, ⏱ spóźniony.</li>
    <li>Możesz dodać notatkę do konkretnego wpisu (np. „Kontuzja, zwolnienie do końca tygodnia").</li>
    <li>Kliknij <strong>Zapisz</strong>. Statystyki frekwencji odświeżają się natychmiast.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/sport/attendance</div>
    <div class="manual-mockup-content">
                <div class="d-flex justify-content-between mb-3"><div><strong>Piłka nożna — seniorzy</strong> · Treningi — 5 maj 2026, 18:00<br><small class="text-muted">Boisko główne · Trener: Jan Kowalski</small></div><div><button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></button> <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></button></div></div>
                <table class="table table-hover">
                    <thead class="table-light"><tr><th>#</th><th>Zawodnik</th><th>Status</th><th>Notatka</th></tr></thead>
                    <tbody>
                        <tr><td>1</td><td>Adamski Michał</td><td><span class="badge bg-success">✓ Obecny</span></td><td></td></tr>
                        <tr><td>2</td><td>Borowski Krzysztof</td><td><span class="badge bg-danger">✗ Nieobecny</span></td><td><small>Brak informacji</small></td></tr>
                        <tr><td>3</td><td>Czerny Patryk</td><td><span class="badge bg-warning text-dark">U Usprawiedliwiony</span></td><td><small>Egzamin szkolny</small></td></tr>
                        <tr><td>4</td><td>Dudek Jakub</td><td><span class="badge bg-info">⏱ Spóźnienie 15 min</span></td><td></td></tr>
                        <tr><td>5</td><td>Erdman Tomasz</td><td><span class="badge bg-success">✓ Obecny</span></td><td></td></tr>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between"><small class="text-muted">Frekwencja: 60% (3/5)</small><div><button class="btn btn-sm btn-outline-secondary">Zaznacz wszystkich obecnych</button> <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Zapisz</button></div></div>
            </div>
    <div class="manual-mockup-caption">Grid obecności z kolorowymi badge'ami statusów i notatkami.</div>
</div>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> QR check-in.</strong> Zawodnicy mogą sami zaznaczać obecność skanując QR-kod wyświetlony na ekranie trenera. Włącz w <em>Ustawienia → Sport → QR check-in</em>.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy mogę edytować obecność wstecz?</summary>
        <div class="faq-body">Tak — administrator klubu może modyfikować dowolny historyczny wpis. Każda zmiana trafia do audit logu z oryginalną i nową wartością.</div>
    </details>
    <details>
        <summary>Jak działają statystyki frekwencji?</summary>
        <div class="faq-body">System liczy procent obecności w okresie (miesiąc/sezon) per zawodnik i sekcja. Raporty w <em>Sport → Statystyki frekwencji</em>.</div>
    </details>
    <details>
        <summary>Co z treningami odwołanymi?</summary>
        <div class="faq-body">Nie wliczają się do statystyk — system pomija je automatycznie.</div>
    </details>
</div>
