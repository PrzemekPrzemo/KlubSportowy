<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Zaznaczanie obecności (mobile-friendly)</h1>
<p class="lead">
    Obecność to zdecydowanie najczęściej używana funkcja panelu trenera. ClubDesk
    został zaprojektowany tak, żebyś mógł oznaczyć całą sekcję w mniej niż 60
    sekund — z telefonu, w hali, na zewnątrz, bez konieczności zdejmowania
    rękawiczek (duże przyciski).
</p>

<h2>Wejście w ekran obecności</h2>
<p>
    Trzy najszybsze drogi:
</p>
<ol>
    <li>Z dashboardu — duża kafelka <em>"Najbliższy trening — zaznacz obecność"</em>.</li>
    <li>Z harmonogramu — kliknięcie w bloczek treningu.</li>
    <li>Z karty sekcji — przycisk <em>"Obecności"</em> w nagłówku.</li>
</ol>

<h2>Grid obecności</h2>
<p>
    Ekran obecności pokazuje zawodników w postaci kart-kafelków, posortowanych
    alfabetycznie po nazwisku (z opcją przełączenia na "kolejność na liście"
    ustaloną w karcie sekcji). Każda karta ma cztery duże przyciski statusów:
</p>
<ul>
    <li><strong>Obecny</strong> (zielony) — zawodnik na treningu.</li>
    <li><strong>Spóźniony</strong> (żółty) — przyszedł, ale po 15 min.</li>
    <li><strong>Nieobecny</strong> (czerwony) — usprawiedliwiony lub nie.</li>
    <li><strong>Zwolniony</strong> (szary) — np. kontuzja, choroba, urlop.</li>
</ul>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/attendance/2026-05-13/skrzaty-u9</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between mb-2">
            <h6 class="mb-0">Obecność — Skrzaty (U-9) · Pn 13.05.2026 17:00</h6>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-check-all"></i> Wszyscy obecni</button>
        </div>
        <div class="row g-2">
            <?php
            $names = [
                'Antoni Kowalski' => 'obecny',
                'Bartek Wójcik'   => 'obecny',
                'Cezary Nowak'    => 'nieobecny',
                'Dawid Lewandowski' => 'spozniony',
                'Emil Zieliński'  => 'obecny',
                'Filip Kowalewski' => 'obecny',
                'Grzegorz Pawlak' => 'zwolniony',
                'Hubert Kowalik'  => 'obecny',
            ];
            $colors = [
                'obecny' => 'success', 'spozniony' => 'warning',
                'nieobecny' => 'danger', 'zwolniony' => 'secondary',
            ];
            $labels = [
                'obecny' => 'Obecny', 'spozniony' => 'Spóźniony',
                'nieobecny' => 'Nieobecny', 'zwolniony' => 'Zwolniony',
            ];
            foreach ($names as $name => $status):
                $color = $colors[$status]; $label = $labels[$status];
            ?>
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= $name ?></strong>
                            <div class="text-muted small">Aktualnie: <span class="text-<?= $color ?>"><?= $label ?></span></div>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-<?= $status==='obecny' ? 'success' : 'outline-success' ?>" title="Obecny">✓</button>
                            <button class="btn btn-<?= $status==='spozniony' ? 'warning' : 'outline-warning' ?>" title="Spóźniony">⏱</button>
                            <button class="btn btn-<?= $status==='nieobecny' ? 'danger' : 'outline-danger' ?>" title="Nieobecny">✗</button>
                            <button class="btn btn-<?= $status==='zwolniony' ? 'secondary' : 'outline-secondary' ?>" title="Zwolniony">—</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 d-flex justify-content-between">
            <small class="text-muted">Auto-zapis: po każdym kliknięciu</small>
            <button class="btn btn-primary"><i class="bi bi-check2"></i> Zakończ trening</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: grid obecności na telefonie. Duże przyciski, jednoznaczne kolory.</div>
</div>

<h2>Auto-zapis</h2>
<p>
    Każde kliknięcie statusu jest zapisywane natychmiast, w tle, do bazy. Nawet
    jeśli stracisz połączenie z internetem, aplikacja PWA zapamięta zmiany i
    zsynchronizuje, gdy tylko Wi-Fi/LTE wróci. Na ekranie zobaczysz wtedy małą
    ikonę chmurki ze strzałką.
</p>

<h2>Szybkie akcje</h2>
<ul>
    <li><strong>Wszyscy obecni</strong> — jeden klik, system oznacza wszystkich
        na zielono. Wtedy odznaczasz tylko brakujących.</li>
    <li><strong>Reset</strong> — czyści wszystkie zaznaczenia (np. gdy zaczynasz
        z czystą kartą).</li>
    <li><strong>Notka</strong> — przy każdym zawodniku ikona ołówka pozwala
        dopisać krótki komentarz ("biegnij więcej Antek!", "lewa kostka boli —
        odpuścił skoki").</li>
</ul>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Z telefonu ekran jest jednokolumnowy. Z tabletu/laptopa — dwukolumnowy.
    Aplikacja sama dobiera układ na podstawie szerokości ekranu.
</div>

<h2>Zamykanie treningu</h2>
<p>
    Po wciśnięciu <em>Zakończ trening</em> system zapisuje finalną wersję
    obecności i lockuje wpis — kolejne edycje wymagają potwierdzenia ("Na pewno
    chcesz zmienić obecność po zamknięciu treningu?"). To zabezpieczenie przed
    przypadkowym dotknięciem w kieszeni.
</p>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
