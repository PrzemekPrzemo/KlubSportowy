<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>SMS przypomnienia</h1>
<p class="lead">
    SMS to najbardziej skuteczny i jednocześnie najdroższy kanał komunikacji
    klubu. ClubDesk oferuje wbudowaną wysyłkę SMS-ów z ograniczonym limitem
    miesięcznym (zależnym od planu klubu) — warto używać ich tam, gdzie naprawdę
    się opłacą.
</p>

<h2>Limit i koszty</h2>
<p>
    Każdy plan klubu obejmuje pulę SMS-ów na miesiąc (np. 100, 500, 2000). Po
    przekroczeniu limit obowiązuje opłata za nadlimit (1 SMS = 8 gr). Status
    limitu widzisz w prawym górnym rogu każdego ekranu wysyłki SMS.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/sms/new</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="mb-0">Wyślij SMS</h6>
            <span class="badge bg-info">Limit: 27/100 (do końca msc)</span>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Odbiorcy</label>
                <select class="form-select" disabled>
                    <option>Zaległościowcy &gt;30 dni (7)</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Szablon</label>
                <select class="form-select" disabled>
                    <option>Przypomnienie zaległej składki</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Treść (max 160 znaków)</label>
                <textarea class="form-control" rows="3" disabled>Dzien dobry {{imie}}, przypominamy o zaleglej skladce {{kwota}}zl, termin minal {{dni_minelo}} dni temu. Link do platnosci: clubdesk.pl/p/{{kod}}. Pozdrawiamy.</textarea>
                <small class="text-muted">Po podstawieniu zmiennych: ok 140 znaków. 1 SMS / osobę.</small>
            </div>
        </div>
        <hr>
        <div class="alert alert-warning small mb-2">
            <strong>Koszt:</strong> 7 SMS-ów × 1 segment = 7 z limitu. <strong>Po wysyłce zostanie 20 SMS-ów do końca miesiąca.</strong>
        </div>
        <button class="btn btn-warning">Wyślij do 7 osób</button>
    </div>
    <div class="manual-mockup-caption">Mockup: kompozytor SMS-ów z licznikiem limitu i szacunkową liczbą segmentów.</div>
</div>

<h2>Długość SMS-a</h2>
<p>
    Standardowy SMS to 160 znaków łacińskich (lub 70 z polskimi znakami
    diakrytycznymi). Dłuższe wiadomości są dzielone na "segmenty" — każdy
    liczony osobno do limitu. ClubDesk pokazuje, ile segmentów zajmie wiadomość,
    żebyś podejmował świadome decyzje.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Wpisywanie SMS-ów bez polskich znaków (np. "skladka" zamiast "składka")
    pozwala zmieścić więcej tekstu w jednym segmencie. Klub typowo wybiera ten
    tryb dla przypomnień finansowych.
</div>

<h2>Najlepsze zastosowania SMS-ów</h2>
<ul>
    <li><strong>Pilne odwołania treningów</strong> — burza, awaria hali.</li>
    <li><strong>Wezwania do zapłaty</strong> (poziom 3 — &gt;21 dni zaległości).</li>
    <li><strong>Przypomnienia o badaniach</strong> (na 7 dni przed wygaśnięciem).</li>
    <li><strong>Przypomnienia o turniejach</strong> (wieczorem przed).</li>
    <li><strong>Komunikacja kryzysowa</strong> — gdy e-mail nie wystarczy.</li>
</ul>

<h2>Czego NIE wysyłać SMS-em</h2>
<ul>
    <li>Długich informacji wymagających załączników.</li>
    <li>Treści marketingowych (chyba że klient wyraził zgodę).</li>
    <li>Życzeń świątecznych do wszystkich (kosztowne i mało skuteczne).</li>
</ul>

<h2>Automatyczne SMS-y</h2>
<p>
    Klub może aktywować automatyczne SMS-y w trzech scenariuszach:
</p>
<ul>
    <li>Przypomnienie o treningu (1h przed) — dla zarejestrowanych w aplikacji
        rodziców, którzy mieli &gt;3 ostatnie nieobecności.</li>
    <li>Wygasające badania (7 dni przed) — dla wszystkich z zarejestrowanym
        numerem.</li>
    <li>Potwierdzenie płatności — dla wartości &gt;500 zł.</li>
</ul>
<p>
    Konfigurację robi zarząd — sekretariat ma podgląd.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    SMS-y są wysyłane <strong>tylko w godzinach 8:00 – 20:00</strong> w dni
    robocze (i 9:00 – 19:00 w weekendy). To ustawowy wymóg ochrony konsumenta —
    nawet jeżeli próbujesz wysłać ręcznie o 22:00, system zaczeka do rana.
</div>

<h2>Statystyki</h2>
<p>
    W <em>Korespondencja → Historia SMS-ów</em> widzisz każdą wysyłkę: do kogo,
    kiedy, treść, status dostarczenia (dostarczony / niedostarczony / błąd
    numeru). Numery uznane przez operatora za "martwe" są oznaczane — warto
    zaktualizować je w karcie członka.
</p>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
