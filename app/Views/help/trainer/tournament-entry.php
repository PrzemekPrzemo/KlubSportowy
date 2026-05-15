<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Zgłoszenie zawodników do turnieju</h1>
<p class="lead">
    Po przyjęciu zaproszenia (lub utworzeniu turnieju przez zarząd) trener
    decyduje, kogo z sekcji zgłasza. ClubDesk pomaga w tym poprzez listę
    uprawnionych zawodników oraz automatyczne walidacje (czy ma badania, czy
    wiek się zgadza, czy nie ma dyscyplinarnego "kary" itp.).
</p>

<h2>Otwarcie zgłoszeń</h2>
<p>
    W karcie turnieju przechodzisz na zakładkę <strong>Zgłoszenia</strong>. Lewa
    kolumna to lista zawodników Twojej sekcji, prawa — lista zgłoszonych. Klikasz
    nazwisko, żeby przenieść (jak w klasycznym multi-select).
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/tournaments/bielsko-cup-2026/entries</div>
    <div class="manual-mockup-content">
        <h6>Bielsko Cup 2026 — Zgłoszenia U-9 (do: 10.05.2026, limit: 16)</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="small text-muted">Moja sekcja (do wyboru)</h6>
                <div class="list-group">
                    <a class="list-group-item d-flex justify-content-between"><span>Cezary Nowak <span class="badge bg-danger ms-2">badania!</span></span><i class="bi bi-arrow-right"></i></a>
                    <a class="list-group-item d-flex justify-content-between"><span>Grzegorz Pawlak</span><i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="small text-muted">Zgłoszeni (12)</h6>
                <div class="list-group" style="max-height:200px; overflow-y:auto;">
                    <a class="list-group-item d-flex justify-content-between"><i class="bi bi-arrow-left"></i> <span>Antoni Kowalski</span></a>
                    <a class="list-group-item d-flex justify-content-between"><i class="bi bi-arrow-left"></i> <span>Bartek Wójcik</span></a>
                    <a class="list-group-item d-flex justify-content-between"><i class="bi bi-arrow-left"></i> <span>Dawid Lewandowski</span></a>
                    <a class="list-group-item d-flex justify-content-between"><i class="bi bi-arrow-left"></i> <span>Emil Zieliński</span></a>
                    <a class="list-group-item d-flex justify-content-between"><i class="bi bi-arrow-left"></i> <span>Filip Kowalewski</span></a>
                    <a class="list-group-item d-flex justify-content-between"><i class="bi bi-arrow-left"></i> <span>Hubert Kowalik</span></a>
                    <a class="list-group-item d-flex justify-content-between"><i class="bi bi-arrow-left"></i> <small class="text-muted">… 6 więcej</small></a>
                </div>
            </div>
        </div>
        <hr>
        <div class="alert alert-warning small mb-2"><strong>2 zawodników</strong> nie zostało automatycznie dodanych: Cezary Nowak (brak ważnych badań), Iza Pawlak (brak zgody RODO).</div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check2"></i> Zatwierdź zgłoszenie</button>
            <button class="btn btn-outline-secondary">Zapisz roboczo</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: zgłoszenia do turnieju. Czerwone bażki oznaczają blokery (badania, RODO).</div>
</div>

<h2>Walidacje automatyczne</h2>
<p>
    Przy próbie zgłoszenia ClubDesk sprawdza w tle:
</p>
<ul>
    <li><strong>Wiek</strong> — czy zawodnik mieści się w kategorii wiekowej turnieju.</li>
    <li><strong>Badania</strong> — czy są ważne na datę turnieju (nie na dziś!).</li>
    <li><strong>RODO</strong> — czy rodzic wyraził zgodę na udział w turniejach.</li>
    <li><strong>Licencja PZ</strong> — czy zawodnik ma aktywną licencję związkową
        (jeśli turniej tego wymaga).</li>
    <li><strong>Zaległości składkowe</strong> — niektóre kluby blokują zgłoszenie
        przy zaległych składkach (regulamin klubu).</li>
</ul>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Blokady są domyślnie "miękkie" — możesz je obejść za zgodą zarządu, klikając
    <em>"Zgłoś mimo wszystko"</em>. System zapisze tę decyzję w logu i wyśle
    notyfikację do zarządu klubu. Bez zgody zarządu jest to niemożliwe.
</div>

<h2>Limit zawodników</h2>
<p>
    Organizator turnieju zawsze podaje limit — np. 14 zawodników w drużynie. Po
    przekroczeniu limitu system nie pozwoli dodać kolejnej osoby. Dla turniejów
    rezerwowych można aktywować "listę rezerwową".
</p>

<h2>Komunikat do rodziców</h2>
<p>
    Po zatwierdzeniu zgłoszenia ClubDesk automatycznie wysyła e-mail/push do
    rodziców zgłoszonych zawodników z informacją o:
</p>
<ul>
    <li>dacie i miejscu turnieju;</li>
    <li>godzinie zbiórki;</li>
    <li>wymaganym sprzęcie;</li>
    <li>opłatach (jeśli są).</li>
</ul>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Możesz <em>oznaczyć kapitana</em> już na etapie zgłoszenia — w karcie
    zgłoszonego klikasz gwiazdkę. Kapitan trafia do listy startowej z gwiazdką
    i jest oznaczony na drabince organizatora.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
