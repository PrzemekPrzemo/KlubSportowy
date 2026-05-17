<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Dashboard sekretariatu — co widzę po zalogowaniu</h1>
<p class="lead">
    Dashboard sekretariatu jest "pulpitem operacyjnym" — pokazuje wszystko, co
    wymaga Twojej uwagi <em>dziś</em>: nowych członków do potwierdzenia, faktury
    do wystawienia, zaległości do przypomnienia, badania do przedłużenia,
    nieodczytane wiadomości.
</p>

<h2>Cztery kafelki "stan klubu"</h2>
<p>
    Na samej górze masz cztery liczby, które dają Ci szybki obraz:
</p>
<ul>
    <li><strong>Aktywni członkowie</strong> — łączna liczba osób z aktywnym członkostwem.</li>
    <li><strong>Wpływy MTD</strong> — wpłaty z bieżącego miesiąca (month to date).</li>
    <li><strong>Zaległe składki</strong> — kwota niezapłaconych faktur powyżej terminu.</li>
    <li><strong>Skuteczność płatności</strong> — % zapłaconych faktur w ostatnich 90 dniach.</li>
</ul>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/dashboard</div>
    <div class="manual-mockup-content">
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card text-center"><div class="card-body p-3"><div class="text-muted small">Aktywni członkowie</div><div class="h3 mb-0">347</div><small class="text-success">+12 w tym mc</small></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body p-3"><div class="text-muted small">Wpływy maj 2026</div><div class="h3 mb-0">87 240 zł</div><small class="text-muted">82% planu mc</small></div></div></div>
            <div class="col-md-3"><div class="card text-center bg-warning-subtle"><div class="card-body p-3"><div class="text-muted small">Zaległe</div><div class="h3 mb-0 text-danger">4 850 zł</div><small class="text-muted">18 osób</small></div></div></div>
            <div class="col-md-3"><div class="card text-center"><div class="card-body p-3"><div class="text-muted small">Skuteczność</div><div class="h3 mb-0">94%</div><small class="text-success">+2 pp / 90 dni</small></div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Wymaga uwagi</strong>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span><i class="bi bi-person-plus text-primary"></i> 3 wnioski o rejestrację</span><a class="btn btn-sm btn-outline-primary">Otwórz</a></li>
                        <li class="list-group-item d-flex justify-content-between"><span><i class="bi bi-heart-pulse text-danger"></i> 7 zawodników: badania wygasają &lt;14 dni</span><a class="btn btn-sm btn-outline-primary">Lista</a></li>
                        <li class="list-group-item d-flex justify-content-between"><span><i class="bi bi-receipt text-warning"></i> 12 faktur do wygenerowania (maj)</span><a class="btn btn-sm btn-outline-primary">Generuj</a></li>
                        <li class="list-group-item d-flex justify-content-between"><span><i class="bi bi-envelope-exclamation text-info"></i> 4 nieodczytane wiadomości od rodziców</span><a class="btn btn-sm btn-outline-primary">Skrzynka</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <strong>Ostatnia aktywność</strong>
                    </div>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item">14:23 — Rejestracja: <strong>Iza Pawlak</strong> (Skrzaty U-9)</li>
                        <li class="list-group-item">12:47 — Płatność: <strong>520 zł</strong> Marta Kowalska</li>
                        <li class="list-group-item">11:30 — Faktura FV-2026/04/138 wygenerowana</li>
                        <li class="list-group-item">10:18 — Aktualizacja: zmiana adresu — Bartek Wójcik</li>
                        <li class="list-group-item">09:02 — Eksport CSV: lista członków (zarząd)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: dashboard sekretariatu. Kolumna "Wymaga uwagi" jest priorytetem dnia.</div>
</div>

<h2>Kolumna "Wymaga uwagi"</h2>
<p>
    To Twoja lista TO-DO dnia. Każdy element to bezpośredni link do konkretnego
    ekranu z akcją, którą trzeba wykonać. Klikając "Otwórz" przy wnioskach o
    rejestrację — trafiasz do listy nieobsłużonych wniosków od trenerów.
    Klikając "Generuj" przy fakturach — uruchamiasz proces masowego generowania.
</p>

<h2>Kolumna "Ostatnia aktywność"</h2>
<p>
    Pokazuje 5 najnowszych zdarzeń w klubie — Twoich oraz Twoich kolegów
    z sekretariatu. Pomaga koordynować pracę kilku osób: widzisz, że Anka właśnie
    zarejestrowała Izę, więc nie próbujesz robić tego samego.
</p>

<h2>Personalizacja</h2>
<p>
    Klikając ikonę koła zębatego w prawym górnym rogu kafelków, możesz wybrać,
    które wskaźniki Cię interesują. Niektórzy preferują np. dodać "Liczba
    członków, którzy mają urodziny w tym tygodniu" — jeden klik wystarczy, by
    wysłać im życzenia z systemu.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Dashboard odświeża się automatycznie co 5 minut. Jeżeli zauważysz, że dane
    wyglądają na "zamrożone" — odśwież ręcznie (F5). To zwykle oznacza, że
    Twoja sesja straciła pierwotny token.
</div>

<h2>Dedykowany ekran /sekretariat</h2>
<p>
    Pod adresem <code>/sekretariat</code> znajduje się <strong>dedykowany dashboard biura</strong>
    przygotowany specjalnie dla roli <em>księgowy</em> oraz <em>zarząd</em>.
    W przeciwieństwie do ogólnego <code>/dashboard</code> (skierowanego do
    trenerów i zarządu) — pulpit biura grupuje wyłącznie zadania związane
    z obsługą członków, fakturami i należnościami.
</p>
<ul>
    <li><strong>Kafelki "do zrobienia"</strong>: nowi członkowie (z ostatnich 7 dni),
        wpłaty bez faktury, zaległe składki, niezapłacone faktury,
        drafty kampanii korespondencji.</li>
    <li><strong>Top 10 zaległości — aging</strong>: lista największych dłużników
        z podziałem na 0–30 / 31–60 / 61–90 / 90+ dni po terminie.</li>
    <li><strong>Badania medyczne</strong>: tylko liczniki (30/14/7 dni do końca ważności)
        — bez ujawniania szczegółów medycznych, do których księgowy nie ma dostępu.</li>
    <li><strong>Quick actions</strong> w nagłówku: Dodaj członka, Import CSV,
        Wystaw fakturę, Eksport CSV.</li>
    <li><strong>Recent activity</strong>: ostatnie 20 wpisów z dziennika dostępu —
        widzisz, że Twoja kolega właśnie zaimportowała plik, więc nie robisz tego samego.</li>
</ul>

<h2>Import członków z pliku CSV</h2>
<p>
    Wejdź w <strong>Biuro → Import członków</strong> (<code>/club/members/import</code>),
    pobierz <strong>wzorzec CSV</strong> (przycisk w prawym górnym rogu), uzupełnij dane
    i wgraj plik. System pokaże podgląd pierwszych 50 wierszy oraz zasugeruje
    mapowanie kolumn. Po potwierdzeniu wykona import w transakcji, a wynik
    (liczba dodanych / pominiętych / błędnych) trafi do dziennika audytu
    (kto, kiedy, ile wierszy).
</p>
<p>
    Limity: maks. <strong>5 MB</strong>, formaty <code>.csv</code> / <code>.txt</code>.
    Numery <strong>PESEL</strong> są szyfrowane w bazie (jeśli klub ma włączoną
    politykę szyfrowania kolumnowego). Duplikaty rozpoznawane są po numerze
    członkowskim oraz adresie email.
</p>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
