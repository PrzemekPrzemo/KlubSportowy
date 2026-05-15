<?php
$page = [
    'title'        => 'Moja frekwencja',
    'category'     => 'Zawodnik',
    'group'        => 'Treningi',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Moja frekwencja</h1>
<p class="lead">Frekwencja to procent treningów, na których byłeś(aś) obecny(a). To ważna informacja — wielu trenerów bierze ją pod uwagę przy ustalaniu składu na zawody, a klub może na jej podstawie przyznawać zniżki na składkach.</p>

<h2>Gdzie sprawdzić frekwencję</h2>
<p>W górnym menu portalu kliknij <strong>Obecność</strong>. Zobaczysz duży licznik z procentem za bieżący miesiąc oraz wykres całorocznej historii.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/attendance</div>
    <div class="manual-mockup-content">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <small class="text-muted">Ten miesiąc</small>
                        <h3 class="mb-0 text-success">88%</h3>
                        <small>7 z 8 treningów</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <small class="text-muted">Ostatnie 3 miesiące</small>
                        <h3 class="mb-0 text-primary">82%</h3>
                        <small>20 z 24 treningów</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <small class="text-muted">Sezon 2025/26</small>
                        <h3 class="mb-0">79%</h3>
                        <small>91 z 115 treningów</small>
                    </div>
                </div>
            </div>
        </div>
        <h6 class="mt-3">Ostatnie treningi</h6>
        <table class="table table-sm">
            <thead><tr><th>Data</th><th>Trening</th><th>Status</th></tr></thead>
            <tbody>
                <tr><td>22.05</td><td>Pływanie · 17:00</td><td><span class="badge bg-success">Obecność</span></td></tr>
                <tr><td>20.05</td><td>Pływanie · 17:00</td><td><span class="badge bg-success">Obecność</span></td></tr>
                <tr><td>18.05</td><td>Pływanie · 17:00</td><td><span class="badge bg-warning text-dark">Nieobecność (usprawiedliwiona)</span></td></tr>
                <tr><td>15.05</td><td>Pływanie · 17:00</td><td><span class="badge bg-success">Obecność</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Ekran obecności z statystykami i ostatnimi wpisami.</div>
</div>

<h2>Co oznaczają statusy</h2>
<ul>
    <li><span class="badge bg-success">Obecność</span> — byłeś(aś) na treningu, trener Cię potwierdził.</li>
    <li><span class="badge bg-warning text-dark">Nieobecność usprawiedliwiona</span> — zgłosiłeś(aś) nieobecność wcześniej (np. choroba, szkoła). Nie wpływa na ranking frekwencji.</li>
    <li><span class="badge bg-danger">Nieobecność</span> — nie pojawiłeś(aś) się bez powodu. Wpływa na frekwencję.</li>
    <li><span class="badge bg-secondary">Spóźnienie</span> — przyszedłeś po rozpoczęciu zajęć. Zwykle liczy się jako obecność, ale trener może to zaznaczyć.</li>
</ul>

<h2>Jak usprawiedliwić nieobecność</h2>
<ol>
    <li>Wejdź w <em>Plan zajęć</em>, znajdź trening, na który nie dotrzesz.</li>
    <li>Kliknij w trening i wybierz <strong>„Zgłoś nieobecność"</strong>.</li>
    <li>Podaj krótki powód (np. „dentysta", „wycieczka szkolna").</li>
    <li>Trener dostanie powiadomienie. Status w Twojej obecności od razu zmieni się na żółty.</li>
</ol>

<div class="manual-info">
    <strong>Po co to wszystko?</strong> Klub używa frekwencji przy planowaniu sezonu, klasyfikacjach i wyborach do reprezentacji. Wysoka frekwencja to nie tylko statystyka — to też lepsze przygotowanie do startów.
</div>

<h2>Eksport danych</h2>
<p>Pod tabelą jest przycisk <strong>Pobierz PDF</strong> — wygenerujesz zaświadczenie o frekwencji (np. do szkoły, na obozy, do stypendium sportowego).</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Trener wpisał mi obecność, choć mnie nie było</summary>
    <p>Napisz do trenera lub sekretariatu. Jeśli to pomyłka — poprawią. Wszystkie zmiany są zapisywane w historii (audit log), więc nic nie ginie.</p>
</details>
<details>
    <summary>Jak liczy się frekwencja, gdy klub odwołuje trening?</summary>
    <p>Odwołane treningi nie są wliczane do procentu. Liczą się tylko te, które rzeczywiście się odbyły.</p>
</details>
<details>
    <summary>Czy frekwencja wpływa na cenę składki?</summary>
    <p>To zależy od klubu — niektóre kluby dają zniżki za frekwencję powyżej 90%. Sprawdź regulamin albo zapytaj sekretariatu.</p>
</details>
