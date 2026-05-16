<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Lista nadchodzących turniejów</h1>
<p class="lead">
    Turnieje to wydarzenia, w których klub bierze udział kolektywnie. Trener
    decyduje, których zawodników z jego sekcji zgłosić, ale samo wydarzenie
    najczęściej tworzy zarząd klubu (po negocjacji z organizatorem).
</p>

<h2>Gdzie znaleźć listę</h2>
<p>
    W lewym menu klikasz <strong>Turnieje</strong>. Domyślny widok to nadchodzące
    wydarzenia z najbliższych 3 miesięcy. Tabela pokazuje:
</p>
<ul>
    <li>Datę i miejsce.</li>
    <li>Nazwę turnieju (z linkiem do regulaminu).</li>
    <li>Kategorię wiekową i poziom (rangę).</li>
    <li>Termin zgłoszeń.</li>
    <li>Status — czy klub się zgłosił, ilu zawodników.</li>
</ul>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/tournaments?filter=upcoming</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between mb-2">
            <h6 class="mb-0">Nadchodzące turnieje</h6>
            <div>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> Filtruj</button>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-calendar"></i> Widok kalendarza</button>
            </div>
        </div>
        <table class="table table-sm table-hover">
            <thead class="table-light">
                <tr><th>Data</th><th>Turniej</th><th>Miejsce</th><th>Kategoria</th><th>Zgłoszenia do</th><th>Status</th></tr>
            </thead>
            <tbody>
                <tr><td>17.05.2026</td><td><strong>Bielsko Cup</strong></td><td>Bielsko-Biała</td><td>U-9</td><td>10.05</td><td><span class="badge bg-success">Zgłoszono 12/14</span></td></tr>
                <tr><td>24.05.2026</td><td><strong>Tarnów Junior Open</strong></td><td>Tarnów</td><td>U-15</td><td>17.05</td><td><span class="badge bg-warning text-dark">Otwarte zgłoszenia</span></td></tr>
                <tr><td>14.06.2026</td><td><strong>Mistrzostwa Małopolski</strong></td><td>Kraków</td><td>U-11</td><td>01.06</td><td><span class="badge bg-info">Wymagana zgoda zarządu</span></td></tr>
                <tr><td>28.06.2026</td><td><strong>Skawina Summer</strong></td><td>Skawina</td><td>U-13</td><td>14.06</td><td><span class="badge bg-secondary">Niezgłoszono</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: tabela nadchodzących turniejów ze statusami zgłoszeń.</div>
</div>

<h2>Filtry</h2>
<p>
    Tabelę możesz filtrować po:
</p>
<ul>
    <li>kategorii wiekowej (U-9, U-11, U-13, …);</li>
    <li>regionie (np. tylko Małopolska);</li>
    <li>poziomie (rangach — od lokalnego do mistrzostw Polski);</li>
    <li>statusie zgłoszenia (zgłoszone / niezgłoszone / wymaga akcji).</li>
</ul>

<h2>Karta turnieju</h2>
<p>
    Klikając w wiersz wchodzisz w kartę turnieju z trzema zakładkami:
</p>
<ul>
    <li><strong>Informacje</strong> — regulamin, harmonogram, miejsce, parking, opłaty.</li>
    <li><strong>Zgłoszenia</strong> — Twoi zawodnicy z możliwością ich dodania (zob. rozdział obok).</li>
    <li><strong>Drabinka i wyniki</strong> — pojawiają się po starcie turnieju.</li>
</ul>

<h2>Powiadomienia o nowych turniejach</h2>
<p>
    Gdy zarząd doda nowy turniej, do którego Twoja kategoria wiekowa jest
    uprawniona — dostajesz powiadomienie push i e-mail. Możesz zarządzać tymi
    powiadomieniami w <em>Mój profil → Preferencje powiadomień</em>.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Filtr <em>"Tylko gdzie mam zgłoszeni zawodnicy"</em> pokazuje listę turniejów,
    w których aktualnie uczestniczysz — przydatny w okresie szczytu sezonu, gdy
    masz po 3 turnieje tygodniowo.
</div>

<h2>Turnieje cykliczne</h2>
<p>
    Niektóre turnieje są cykliczne (np. liga regionalna — co dwa tygodnie). W
    takim wypadku w tabeli widzisz oznaczenie <em>seria</em> i kliknięcie pokaże
    wszystkie kolejki sezonu. Zgłoszeń dokonuje się raz, na cały cykl.
</p>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
