<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Harmonogram treningów</h1>
<p class="lead">
    Harmonogram pokazuje Twój kalendarz w widoku tygodnia lub miesiąca z naniesionymi
    treningami, meczami i turniejami wszystkich sekcji, w których pracujesz. To
    Twoje narzędzie planowania pracy — i jednocześnie miejsce, z którego wchodzisz
    w obecności.
</p>

<h2>Widok tygodnia</h2>
<p>
    Domyślny widok to <strong>tydzień</strong>. Lewa kolumna to godziny (06:00 –
    22:00), nagłówki kolumn to dni od poniedziałku do niedzieli. Treningi są
    kolorowane wg sekcji — Skrzaty na zielono, Orliki na niebiesko itd. Mecze
    i turnieje mają inny styl ramki (gruba ramka, ikona w rogu).
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/schedule?view=week</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Tydzień 12 – 18 maja 2026</h6>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary">‹</button>
                <button class="btn btn-outline-secondary">Dziś</button>
                <button class="btn btn-outline-secondary">›</button>
            </div>
        </div>
        <table class="table table-bordered table-sm text-center">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;"></th>
                    <th>Pn 12</th><th>Wt 13</th><th>Śr 14</th><th>Czw 15</th><th>Pt 16</th><th>Sob 17</th><th>Nd 18</th>
                </tr>
            </thead>
            <tbody>
                <tr><th>17:00</th>
                    <td style="background:#d1e7dd;">Skrzaty</td>
                    <td style="background:#cfe2ff;">Orliki A</td>
                    <td style="background:#d1e7dd;">Skrzaty</td>
                    <td style="background:#cfe2ff;">Orliki A</td>
                    <td></td><td></td><td></td>
                </tr>
                <tr><th>18:30</th>
                    <td style="background:#fff3cd;">Młodzik</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="background:#fff3cd;">Młodzik</td>
                    <td></td><td></td>
                </tr>
                <tr><th>19:00</th>
                    <td></td>
                    <td style="background:#f8d7da;">Junior</td>
                    <td></td>
                    <td></td>
                    <td style="background:#f8d7da;">Junior</td>
                    <td></td><td></td>
                </tr>
                <tr><th>09:00</th>
                    <td></td><td></td><td></td><td></td><td></td>
                    <td style="background:#cff4fc; font-weight:600;">⚽ Turniej Bielsko</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: widok tygodnia trenera. Kliknięcie w bloczek otwiera ekran obecności.</div>
</div>

<h2>Widok miesiąca</h2>
<p>
    Przełącznik w prawym górnym rogu zmienia widok na <strong>miesiąc</strong>.
    Kalendarz pokazuje skondensowaną listę wydarzeń każdego dnia. Przydatny, gdy
    planujesz urlop albo chcesz zobaczyć, w które weekendy są turnieje.
</p>

<h2>Edycja pojedynczego wydarzenia</h2>
<p>
    Klikając w trening trafiasz na jego kartę. Tu możesz:
</p>
<ul>
    <li>Zaznaczyć obecności (najczęstsza akcja).</li>
    <li>Dopisać krótką notatkę z planu treningowego.</li>
    <li>Odwołać trening (po wpisaniu powodu) — system wyśle automatyczne
        powiadomienia do wszystkich zawodników sekcji.</li>
    <li>Poprosić o zastępstwo (zob. rozdział o substytucjach).</li>
</ul>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Jeżeli klub ma zintegrowany Google Calendar (zarząd ustawia w
    integracjach), Twój harmonogram pojawi się jako subskrybowany kalendarz
    również w aplikacji Google Calendar na telefonie.
</div>

<h2>Wyjątki w cyklu</h2>
<p>
    Cykliczne treningi są wyjątkowo wygodne, ale czasem trzeba zrobić odstępstwo —
    np. przesunąć trening z poniedziałku Świątecznego na czwartek. Klikasz wtedy
    konkretne wystąpienie w kalendarzu i wybierasz <em>Przesuń to wystąpienie</em>
    (bez naruszania pozostałych dat) lub <em>Edytuj cały cykl</em> (zmiana
    permanentna).
</p>

<h2>Filtry sekcji</h2>
<p>
    Jeżeli prowadzisz kilka grup i chcesz zobaczyć tylko jedną — w lewym górnym
    rogu rozwija się lista <em>Sekcje</em>, gdzie odznaczasz pozostałe. Pamiętaj,
    że to filtr wizualny — nie usuwa wydarzeń, tylko je ukrywa.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Cykle treningów planuje zarząd klubu (albo trener-koordynator) — pojedynczy
    trener nie tworzy nowych cykli. Możesz tylko edytować pojedyncze wystąpienia.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
