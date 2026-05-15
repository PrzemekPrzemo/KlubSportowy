<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Substytucje — zastępstwo innego trenera</h1>
<p class="lead">
    Choroba, wyjazd służbowy, urlop — w pracy trenera czasem trzeba przekazać
    trening koledze. ClubDesk ma dedykowany mechanizm <em>substytucji</em>, który
    pilnuje, żeby wszystko (obecności, prowizje, wgląd w sekcję) zostało
    poprawnie zarejestrowane.
</p>

<h2>Poproszenie o zastępstwo</h2>
<p>
    Wchodzisz w konkretne wystąpienie treningu w harmonogramie i klikasz
    <strong>Poproś o zastępstwo</strong>. Otwiera się okno, w którym:
</p>
<ol>
    <li>Wybierasz trenera (z listy aktywnych trenerów w klubie).</li>
    <li>Wpisujesz powód (informacja dla zastępcy i zarządu).</li>
    <li>Decydujesz, czy zachować swoją prowizję, czy oddać ją zastępcy.</li>
    <li>Wysyłasz prośbę — zastępca dostaje powiadomienie.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/sessions/2026-05-15-skrzaty/substitute</div>
    <div class="manual-mockup-content">
        <h6>Prośba o zastępstwo — Skrzaty (U-9) · Pt 15.05 17:00</h6>
        <div class="mb-3">
            <label class="form-label">Zastępca</label>
            <select class="form-select" disabled>
                <option>Marek Krawczyk (Orliki B)</option>
                <option selected>Tomasz Lewandowski (Junior)</option>
                <option>Anna Wójcik (Skrzaty B)</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Powód</label>
            <textarea class="form-control" rows="2" disabled>Wizyta lekarska — odbiorę punkty na drugim treningu.</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Prowizja</label>
            <div class="form-check">
                <input type="radio" class="form-check-input" disabled>
                <label class="form-check-label">Zachowuję — to mój trening, oddam wymianowo.</label>
            </div>
            <div class="form-check">
                <input type="radio" class="form-check-input" checked disabled>
                <label class="form-check-label">Oddaję zastępcy (zalecane jeśli nie planuję rewanżu).</label>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary">Wyślij prośbę</button>
            <button class="btn btn-outline-secondary">Anuluj</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: formularz prośby o zastępstwo. Decyzja o prowizji jest istotna księgowo.</div>
</div>

<h2>Akceptacja przez zastępcę</h2>
<p>
    Zastępca dostaje powiadomienie (push + e-mail) i może <strong>zaakceptować
    lub odrzucić</strong>. Po akceptacji:
</p>
<ul>
    <li>Sesja w jego kalendarzu pojawia się na żółto z ikoną "zastępstwo".</li>
    <li>Zastępca ma pełen dostęp do tej konkretnej sekcji <em>tylko na czas tego
        treningu</em> — może zaznaczyć obecności i zapisać notatki.</li>
    <li>Po treningu nie ma już dostępu do Twojej sekcji — automatycznie traci
        widoczność.</li>
</ul>

<h2>Co jeśli nikt nie zaakceptuje</h2>
<p>
    Jeśli prośba o zastępstwo pozostaje bez odpowiedzi do 24 godzin przed
    treningiem, system <strong>eskaluje</strong> do trenera-koordynatora lub
    zarządu. To oznacza, że zarząd musi wybrać zastępcę manualnie albo odwołać
    trening.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Jeżeli ostatecznie trening odwołasz (zamiast szukać zastępcy), Twoja
    prowizja za ten trening nie zostanie naliczona — nawet jeśli wcześniej w
    miesiącu zaakceptowałeś dwa "podwójne". Klub może też wprowadzić sankcje
    za zbyt częste odwołania (statystyka jest widoczna w profilu trenera).
</div>

<h2>Rewanż</h2>
<p>
    Jeśli zostawiłeś prowizję u siebie ("oddam wymianowo"), system pamięta
    dług trenerski. W zakładce <em>Mój profil → Zastępstwa</em> widzisz listę
    "winnych" i "wierzytelności" — komu i ile treningów wisisz, kto wisi Tobie.
    Po przeprowadzeniu rewanżu klikasz <em>Oznacz jako rozliczone</em>.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Najprostsze rozwiązania są najlepsze — w 90% przypadków warto wybrać opcję
    "Oddaję zastępcy" zamiast prowadzić księgowość "winnych treningów" wewnątrz
    siebie samego.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
