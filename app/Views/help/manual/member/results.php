<?php
$page = [
    'title'        => 'Wyniki i turnieje',
    'category'     => 'Zawodnik',
    'group'        => 'Wyniki',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Wyniki i turnieje</h1>
<p class="lead">Tu znajdziesz pełną historię swoich startów: zawodów, meczów, turniejów. Możesz przeglądać miejsca, zdobyte punkty, wyniki pojedynczych spotkań, a nawet zobaczyć drabinkę całego turnieju i sprawdzić jak grali rywale.</p>

<h2>Gdzie szukać wyników</h2>
<p>W menu portalu kliknij <strong>Wyniki</strong>. Ekran zaczyna się od podsumowania (ile startów, ile miejsc na podium), a niżej masz listę wszystkich turniejów.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/results</div>
    <div class="manual-mockup-content">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card text-center"><div class="card-body">
                    <small class="text-muted">Starty 2026</small>
                    <h3 class="mb-0">7</h3>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card text-center"><div class="card-body">
                    <small class="text-muted">Złoto</small>
                    <h3 class="mb-0 text-warning">1 🥇</h3>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card text-center"><div class="card-body">
                    <small class="text-muted">Srebro</small>
                    <h3 class="mb-0" style="color:#aaa;">2 🥈</h3>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card text-center"><div class="card-body">
                    <small class="text-muted">Brąz</small>
                    <h3 class="mb-0" style="color:#cd7f32;">0 🥉</h3>
                </div></div>
            </div>
        </div>
        <h6>Ostatnie starty</h6>
        <table class="table table-sm align-middle">
            <thead><tr><th>Data</th><th>Turniej</th><th>Konkurencja</th><th>Miejsce</th><th>Wynik</th></tr></thead>
            <tbody>
                <tr>
                    <td>04.05.2026</td>
                    <td>Mistrzostwa Wojewódzkie U18</td>
                    <td>100 m stylem dowolnym</td>
                    <td><span class="badge bg-warning text-dark">🥇 1.</span></td>
                    <td>1:02.18</td>
                </tr>
                <tr>
                    <td>20.04.2026</td>
                    <td>Puchar Iskry</td>
                    <td>50 m stylem dowolnym</td>
                    <td><span class="badge bg-secondary">🥈 2.</span></td>
                    <td>28.45</td>
                </tr>
                <tr>
                    <td>18.03.2026</td>
                    <td>Memoriał Kowalskiego</td>
                    <td>100 m grzbietowy</td>
                    <td><span class="badge bg-light text-dark border">5.</span></td>
                    <td>1:14.92</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Statystyki sezonu plus tabela ostatnich startów z czasami i miejscami.</div>
</div>

<h2>Co zobaczysz po kliknięciu w turniej</h2>
<ul>
    <li><strong>Twoje miejsce</strong> w klasyfikacji końcowej.</li>
    <li><strong>Każdy pojedynek / start</strong> z osobnym wynikiem.</li>
    <li><strong>Drabinka turniejowa</strong> — w sportach na drabinki (tenis, BJJ, MMA).</li>
    <li><strong>Mapa miejsca</strong> i godzina rozegrania.</li>
    <li><strong>Protokół PDF</strong> oficjalny od organizatora (jeśli klub wgrał).</li>
    <li><strong>Zdjęcia</strong> — fotorelacja, jeśli klub udostępnił.</li>
</ul>

<h2>Punktacja klubowa</h2>
<p>Wiele klubów prowadzi swój wewnętrzny ranking — za każdy start, miejsce na podium lub czas dostajesz punkty, które sumują się w sezonie. To podstawa do wyborów reprezentacji, nagród rocznych albo zniżek na składkach.</p>

<div class="manual-info">
    <strong>Cross-sport.</strong> Jeśli trenujesz kilka dyscyplin (np. pływanie + triathlon), portal pokazuje też wspólny ranking „cross-sport" — sumę osiągnięć ze wszystkich Twoich sekcji.
</div>

<h2>Filtrowanie i eksport</h2>
<p>Nad listą znajdziesz filtry: rok, sezon, dyscyplina, ranga turnieju (lokalny, regionalny, krajowy, międzynarodowy). Pod listą jest przycisk <strong>Pobierz PDF</strong> — przyda się do CV sportowego, stypendium, zgłoszenia do szkoły mistrzostwa sportowego.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Brakuje wyniku ostatniego turnieju</summary>
    <p>Wyniki wprowadza sędzia lub klub po zawodach. Czasami zajmuje to kilka dni — szczególnie jeśli czekają na oficjalne protokoły organizatora.</p>
</details>
<details>
    <summary>Mój wynik jest błędny</summary>
    <p>Napisz do trenera albo sekretariatu klubu. Wszystkie zmiany są logowane, więc jeśli była pomyłka, łatwo ją skorygować.</p>
</details>
<details>
    <summary>Czy mogę zobaczyć wyniki kolegów z klubu?</summary>
    <p>Tak, ale tylko za zgodą drugiego zawodnika (publiczny profil) albo dla zawodników z Twojego klubu, jeśli to umożliwia.</p>
</details>
