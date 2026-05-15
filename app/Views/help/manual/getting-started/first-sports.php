<?php /** Dodanie pierwszych sekcji sportowych */ ?>
<p class="lead">Sekcja sportowa to podstawowa jednostka organizacyjna w ClubDesk — grupuje członków, treningi, turnieje, składki i wyniki w ramach jednej dyscypliny. Klub może prowadzić wiele sekcji równocześnie (np. piłka nożna + siatkówka).</p>

<h2>Dostępne dyscypliny</h2>
<p>ClubDesk wspiera 12 dyscyplin z dedykowanymi modułami: piłka nożna, siatkówka, koszykówka, hokej, lekkoatletyka, strzelectwo, judo, tenis stołowy, jeździectwo, wędkarstwo, pływanie, kolarstwo. Każdy moduł zawiera specyficzne dla dyscypliny pola (np. waga w judo, kaliber w strzelectwie, koń w jeździectwie) oraz dedykowane raporty.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Przejdź do <strong>Klub → Sekcje sportowe</strong>.</li>
    <li>Kliknij <em>+ Dodaj sekcję</em>.</li>
    <li>Wybierz dyscyplinę z listy (pojawi się dedykowana ikona).</li>
    <li>Wpisz nazwę sekcji (np. <code>Piłka nożna — seniorzy</code>, <code>Siatkówka U-16</code>).</li>
    <li>Określ kategorie wiekowe i poziomowe (np. <em>Junior młodszy, Junior, Senior</em>).</li>
    <li>Przypisz głównego trenera (musi mieć konto z rolą <em>trener</em>).</li>
    <li>Ustaw widoczność: <em>publiczna</em> (widoczna na stronie klubu) lub <em>prywatna</em>.</li>
    <li>Kliknij <strong>Zapisz</strong>.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/club/sports</div>
    <div class="manual-mockup-content">
        <h6 class="mb-3"><i class="bi bi-trophy"></i> Sekcje sportowe klubu</h6>
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>Sekcja</th><th>Dyscyplina</th><th>Trener</th><th>Członków</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <tr><td><i class="bi bi-circle-fill text-success me-1"></i> Piłka nożna — seniorzy</td><td><span class="badge bg-light text-dark">⚽ Football</span></td><td>Jan Kowalski</td><td>24</td><td><span class="badge bg-success">Aktywna</span></td><td><a href="#" class="btn btn-sm btn-outline-secondary">Edytuj</a></td></tr>
                <tr><td><i class="bi bi-circle-fill text-success me-1"></i> Siatkówka — U-16</td><td><span class="badge bg-light text-dark">🏐 Volleyball</span></td><td>Anna Nowak</td><td>15</td><td><span class="badge bg-success">Aktywna</span></td><td><a href="#" class="btn btn-sm btn-outline-secondary">Edytuj</a></td></tr>
                <tr><td><i class="bi bi-circle-fill text-warning me-1"></i> Strzelectwo sportowe</td><td><span class="badge bg-light text-dark">🎯 Shooting</span></td><td>—</td><td>8</td><td><span class="badge bg-warning text-dark">W przygotowaniu</span></td><td><a href="#" class="btn btn-sm btn-outline-secondary">Edytuj</a></td></tr>
            </tbody>
        </table>
        <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Dodaj sekcję</button>
    </div>
    <div class="manual-mockup-caption">Lista sekcji sportowych klubu z trenerami głównymi i liczbą członków.</div>
</div>

<h2>Dobre praktyki</h2>
<div class="manual-callout manual-callout-tip">
    <strong>Nazewnictwo.</strong> Trzymaj się schematu <em>Dyscyplina — kategoria</em> (np. „Koszykówka — Junior") zamiast nazw własnych typu „Drużyna Kowalskiego". Ułatwi to filtrowanie raportów i import członków.
</div>

<h2>Co jest powiązane z sekcją</h2>
<ul>
    <li>Lista członków (zawodników) przypisanych do dyscypliny.</li>
    <li>Plan treningów (recurring) i obecność.</li>
    <li>Składki członkowskie — możesz ustawić różną stawkę dla każdej sekcji.</li>
    <li>Turnieje i rozgrywki sezonowe.</li>
    <li>Ranking i statystyki cross-sport (jeśli zawodnik trenuje w kilku sekcjach).</li>
    <li>Dokumenty federacyjne i licencje zawodnicze.</li>
</ul>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details><summary>Czy mogę później zmienić dyscyplinę?</summary><div class="faq-body">Nie — dyscyplina determinuje strukturę danych (specyficzne pola, statystyki). Możesz natomiast zarchiwizować sekcję i utworzyć nową.</div></details>
    <details><summary>Co jeśli mojej dyscypliny nie ma na liście?</summary><div class="faq-body">Wybierz dyscyplinę <em>Inne</em> z generycznymi polami lub napisz na <code>support@clubdesk.pl</code> z prośbą o dodanie modułu — typowy czas dodania nowej dyscypliny to 4-6 tygodni.</div></details>
    <details><summary>Czy zawodnik może być w wielu sekcjach?</summary><div class="faq-body">Tak — to częsta sytuacja w klubach wielosekcyjnych. Każde przypisanie ma osobną historię obecności, składek i wyników.</div></details>
</div>
