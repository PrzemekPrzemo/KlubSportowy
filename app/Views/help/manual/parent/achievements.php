<?php
$page = [
    'title'        => 'Wyniki i osiągnięcia dziecka',
    'category'     => 'Rodzic',
    'group'        => 'Aktywności',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Wyniki i osiągnięcia dziecka</h1>
<p class="lead">Każde dziecko ma w klubie własne „CV sportowe" — listę startów, miejsc na podium, rekordów życiowych i zdobytych odznak. Jako rodzic widzisz wszystko: ostatnie zawody, statystyki sezonu, postępy w czasie. To świetna motywacja zarówno dla dziecka, jak i dla Ciebie — fajnie obserwować, jak rośnie.</p>

<h2>Gdzie znaleźć wyniki</h2>
<p>W profilu dziecka są dwie zakładki:</p>
<ul>
    <li><strong>Wyniki</strong> — historia startów, czasów, miejsc.</li>
    <li><strong>Osiągnięcia</strong> — odznaki (achievementy) za frekwencję, sukcesy, lojalność.</li>
</ul>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/guardian/ward/142/results</div>
    <div class="manual-mockup-content">
        <h5 class="mb-3"><i class="bi bi-trophy text-warning"></i> Wyniki: Anna Kowalska</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><small class="text-muted">Starty 2026</small><h4 class="mb-0">7</h4></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><small class="text-muted">Złoto</small><h4 class="mb-0 text-warning">1 🥇</h4></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><small class="text-muted">Srebro</small><h4 class="mb-0" style="color:#aaa;">2 🥈</h4></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body"><small class="text-muted">Brąz</small><h4 class="mb-0" style="color:#cd7f32;">0 🥉</h4></div></div></div>
        </div>
        <h6>Ostatnie starty</h6>
        <table class="table table-sm align-middle">
            <thead><tr><th>Data</th><th>Turniej</th><th>Konkurencja</th><th>Miejsce</th><th>Czas</th></tr></thead>
            <tbody>
                <tr><td>04.05.2026</td><td>Mistrzostwa Wojewódzkie U18</td><td>100m dowolny</td><td>🥇 1.</td><td>1:02.18 <span class="badge bg-success">PB</span></td></tr>
                <tr><td>20.04.2026</td><td>Puchar Iskry</td><td>50m dowolny</td><td>🥈 2.</td><td>28.45</td></tr>
                <tr><td>18.03.2026</td><td>Memoriał Kowalskiego</td><td>100m grzbietowy</td><td>5.</td><td>1:14.92</td></tr>
            </tbody>
        </table>
        <hr>
        <h6>Najnowsze odznaki</h6>
        <div class="d-flex gap-3 flex-wrap">
            <div class="text-center"><div style="font-size:2.5rem;">🥇</div><small>Pierwsze złoto</small></div>
            <div class="text-center"><div style="font-size:2.5rem;">💯</div><small>100 treningów</small></div>
            <div class="text-center"><div style="font-size:2.5rem;">🔥</div><small>Seria 10 obecności</small></div>
        </div>
    </div>
    <div class="manual-mockup-caption">Cały sezon dziecka na jednym ekranie — wyniki + odznaki.</div>
</div>

<h2>Co znajdziesz w wynikach</h2>
<ul>
    <li><strong>Pojedyncze starty</strong> — data, miejsce, konkurencja, wynik.</li>
    <li><strong>Rekordy życiowe (PB)</strong> — system automatycznie zaznacza, gdy dziecko bije swój własny rekord.</li>
    <li><strong>Drabinki / klasyfikacje</strong> — dla sportów drabinkowych pełna struktura turnieju.</li>
    <li><strong>Punkty rankingowe</strong> — jeśli klub prowadzi własny ranking.</li>
    <li><strong>Protokoły PDF</strong> — oficjalne dokumenty od organizatorów (do CV, stypendium).</li>
    <li><strong>Zdjęcia</strong> — fotorelacje z turniejów (jeśli klub udostępnił).</li>
</ul>

<h2>Odznaki (achievementy)</h2>
<p>System nadaje odznaki automatycznie za różne osiągnięcia:</p>
<ul>
    <li><strong>Frekwencja</strong>: pierwszy trening, 100 treningów, miesiąc z 100% obecnością.</li>
    <li><strong>Sport</strong>: pierwsza wygrana, 3 medale, podium na zawodach krajowych.</li>
    <li><strong>Społeczność</strong>: udział w klubowych eventach, wsparcie młodszych.</li>
</ul>

<div class="manual-tip">
    <strong>Dla rodzica.</strong> Achievementy to świetny sposób, żeby pochwalić dziecko za drobne sukcesy. Klub często wręcza dyplomy lub gadżety za zdobycie określonej liczby odznak — sprawdź ofertę.
</div>

<h2>Eksport — CV sportowe dziecka</h2>
<p>Pod listą jest przycisk <strong>Pobierz CV sportowe</strong> — generuje elegancki PDF z fotografią, listą startów i odznak. Przyda się przy:</p>
<ul>
    <li>Aplikacji do szkoły mistrzostwa sportowego.</li>
    <li>Wniosku o stypendium sportowe (gmina, ministerstwo).</li>
    <li>Zgłoszeniu do reprezentacji okręgu / regionu.</li>
    <li>Po prostu — żeby pochwalić się sukcesami w rodzinie.</li>
</ul>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Brakuje wyniku z ostatnich zawodów</summary>
    <p>Wyniki trafiają do portalu po zatwierdzeniu protokołów przez organizatora — zwykle 2–5 dni. Jeśli minęło dłużej, napisz do trenera.</p>
</details>
<details>
    <summary>Wynik dziecka jest błędny</summary>
    <p>Skontaktuj się z trenerem lub sekretariatem. Wszystkie zmiany są logowane, więc poprawienie pomyłki to chwila.</p>
</details>
<details>
    <summary>Czy mogę udostępnić wyniki dziecka rodzinie?</summary>
    <p>Tak. Z PDF-em można zrobić co tylko zechcesz. Dodatkowo, jeśli włączysz publiczny profil sportowy dziecka (w jego ustawieniach RODO), będzie widoczny pod linkiem.</p>
</details>
