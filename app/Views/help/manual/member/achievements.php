<?php
$page = [
    'title'        => 'Statystyki i odznaki',
    'category'     => 'Zawodnik',
    'group'        => 'Wyniki',
    'last_updated' => '2026-05-15',
    'reading_time' => '4 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Statystyki i odznaki (achievements)</h1>
<p class="lead">Achievementy to coś jak odznaki w grze — system automatycznie nadaje Ci znaczki za różne osiągnięcia: pierwszy trening, 50 obecności pod rząd, pierwszy medal, sukces sezonowy. To miły sposób na śledzenie postępów i motywację do trenowania dalej.</p>

<h2>Gdzie znaleźć odznaki</h2>
<p>W menu portalu kliknij <strong>Moje osiągnięcia</strong>. Zobaczysz galerię wszystkich odznak — zdobytych (kolorowych) i wciąż do zdobycia (wyszarzonych, z podpowiedzią co trzeba zrobić).</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/achievements</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-trophy-fill text-warning"></i> Moje osiągnięcia</h5>
            <div>
                <span class="badge bg-success">Zdobyte: 8 / 24</span>
                <span class="badge bg-info ms-1">Postęp: 33%</span>
            </div>
        </div>

        <h6 class="mt-3">Frekwencja</h6>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body p-2">
                        <div style="font-size:2.5rem;">🏅</div>
                        <strong class="d-block small">Pierwszy trening</strong>
                        <small class="text-muted">Zdobyte 02.09.2025</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body p-2">
                        <div style="font-size:2.5rem;">💯</div>
                        <strong class="d-block small">100 treningów</strong>
                        <small class="text-muted">Zdobyte 14.04.2026</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center" style="opacity:.4;">
                    <div class="card-body p-2">
                        <div style="font-size:2.5rem;">🔥</div>
                        <strong class="d-block small">Seria 20 obecności</strong>
                        <small class="text-muted">Postęp 12/20</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center" style="opacity:.4;">
                    <div class="card-body p-2">
                        <div style="font-size:2.5rem;">🌟</div>
                        <strong class="d-block small">100% w miesiącu</strong>
                        <small class="text-muted">Postęp 7/8 maj</small>
                    </div>
                </div>
            </div>
        </div>

        <h6 class="mt-4">Sport</h6>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body p-2">
                        <div style="font-size:2.5rem;">🥇</div>
                        <strong class="d-block small">Pierwsze złoto</strong>
                        <small class="text-muted">04.05.2026</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center" style="opacity:.4;">
                    <div class="card-body p-2">
                        <div style="font-size:2.5rem;">🏆</div>
                        <strong class="d-block small">3 medale</strong>
                        <small class="text-muted">Postęp 1/3</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Galeria odznak — kolorowe to zdobyte, przygaszone to wyzwania do zrealizowania.</div>
</div>

<h2>Kategorie odznak</h2>
<ul>
    <li><strong>Frekwencja</strong> — za regularność: serie obecności, pełny miesiąc, rok bez przerwy.</li>
    <li><strong>Sport</strong> — za medale, miejsca w klasyfikacji, rekordy życiowe.</li>
    <li><strong>Społeczność</strong> — za udział w wydarzeniach klubowych, wspieranie młodszych zawodników.</li>
    <li><strong>Cele osobiste</strong> — odznaki, które trener lub Ty sam(a) możesz sobie ustalić.</li>
</ul>

<h2>Jak zdobywać odznaki</h2>
<p>Większość odznak zdobywasz automatycznie — system co noc analizuje Twoje dane (obecności, wyniki) i nadaje brakujące. Ale są też odznaki, które Ty „włączasz" — np. wyzwanie „Trenuj codziennie przez 30 dni". Wchodzisz w nie z ekranu Osiągnięcia i klikasz <strong>Wystartuj wyzwanie</strong>.</p>

<div class="manual-tip">
    <strong>Powiadomienia.</strong> Kiedy zdobędziesz odznakę, dostajesz push na telefon i kolorowe powiadomienie w portalu. Możesz pochwalić się odznaką na profilu publicznym albo zachować dla siebie.
</div>

<h2>Ranking klubowy</h2>
<p>Pod listą odznak jest sekcja <em>Ranking klubu</em> — zobaczysz swoje miejsce na tle innych zawodników (anonimowo, jeśli klub tak ustawi). To zdrowa rywalizacja, która motywuje całą grupę.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Wykonałem(am) warunek, ale odznaka się nie pojawiła</summary>
    <p>System sprawdza odznaki raz na dobę (zwykle w nocy). Daj mu kilka godzin. Jeśli po dobie nadal brak — napisz do sekretariatu.</p>
</details>
<details>
    <summary>Czy odznaki dają realne nagrody?</summary>
    <p>Zależy od klubu. Niektóre kluby przyznają zniżki, gadżety albo wyjazdy za zdobycie określonego progu odznak. Spytaj zarządu klubu, jaką mają politykę.</p>
</details>
<details>
    <summary>Czy mogę zobaczyć odznaki kolegów?</summary>
    <p>Tylko jeśli mają włączony profil publiczny. Domyślnie odznaki są prywatne.</p>
</details>
