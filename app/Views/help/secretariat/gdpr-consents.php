<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Zgody RODO</h1>
<p class="lead">
    Polityka RODO dla klubów sportowych jest niezwykle ważna — przechowujesz
    wrażliwe dane dzieci, ich zdrowie, wizerunek. ClubDesk pomaga prowadzić
    rejestr zgód, automatycznie reagować na żądania (RODO art. 15, 16, 17, 18,
    20) i utrzymywać zgodność z polskim prawem ochrony danych osobowych.
</p>

<h2>Cztery typy zgód</h2>
<ul>
    <li><strong>Zgoda na przetwarzanie</strong> (obowiązkowa) — bez niej nie da
        się utrzymywać członkostwa.</li>
    <li><strong>Zgoda na przetwarzanie danych szczególnych kategorii</strong>
        (zdrowie) — wymagana, jeśli klub gromadzi dokumenty medyczne.</li>
    <li><strong>Zgoda na wizerunek</strong> (opcjonalna) — zdjęcia z treningów,
        publikacje w social media.</li>
    <li><strong>Zgoda na newsletter</strong> (opcjonalna) — wiadomości marketingowe.</li>
</ul>

<h2>Rejestr zgód</h2>
<p>
    W <strong>Compliance → Zgody RODO</strong> widzisz tabelę wszystkich
    aktywnych zgód: kto, kiedy wyraził, czy nadal aktywna, czy została cofnięta.
    Każda zgoda ma <strong>cyfrowy odcisk</strong> (hash) — to gwarancja, że
    dokument nie został później sfałszowany.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/compliance/gdpr</div>
    <div class="manual-mockup-content">
        <h6>Zgody RODO — Antoni Kowalski (147)</h6>
        <table class="table table-sm">
            <thead class="table-light">
                <tr><th>Typ zgody</th><th>Data</th><th>Sposób</th><th>Status</th><th>Akcje</th></tr>
            </thead>
            <tbody>
                <tr><td>Przetwarzanie (obowiązkowe)</td><td>2024-09-01</td><td>e-zgoda + SMS</td><td><span class="badge bg-success">Aktywna</span></td><td><i class="bi bi-eye"></i> <i class="bi bi-download"></i></td></tr>
                <tr><td>Dane szczególne (zdrowie)</td><td>2024-09-01</td><td>e-zgoda + SMS</td><td><span class="badge bg-success">Aktywna</span></td><td><i class="bi bi-eye"></i> <i class="bi bi-download"></i></td></tr>
                <tr><td>Wizerunek</td><td>2024-09-01</td><td>e-zgoda + SMS</td><td><span class="badge bg-success">Aktywna</span></td><td><i class="bi bi-eye"></i> <i class="bi bi-download"></i></td></tr>
                <tr><td>Newsletter</td><td>2025-03-12</td><td>papierowo</td><td><span class="badge bg-warning text-dark">Cofnięta 2026-04-08</span></td><td><i class="bi bi-eye"></i></td></tr>
            </tbody>
        </table>
        <hr>
        <h6 class="mt-3">Żądania RODO</h6>
        <table class="table table-sm">
            <thead class="table-light"><tr><th>Data wpłynięcia</th><th>Typ żądania</th><th>Status</th><th>Termin realizacji</th></tr></thead>
            <tbody>
                <tr><td>2026-04-08</td><td>Cofnięcie zgody newsletter (art. 7)</td><td><span class="badge bg-success">Zrealizowane</span></td><td>2026-04-09</td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: rejestr zgód RODO i historia żądań od członka.</div>
</div>

<h2>Cofanie zgody</h2>
<p>
    Każdą zgodę (poza obowiązkową) członek może cofnąć w dowolnym momencie:
</p>
<ul>
    <li>Z poziomu aplikacji — <em>Mój profil → Zgody → Cofnij</em>.</li>
    <li>Mailem do sekretariatu (Ty wprowadzasz cofnięcie).</li>
    <li>Telefonicznie (wymaga potwierdzenia tożsamości i odnotowania).</li>
</ul>
<p>
    Wpisujesz w karcie członka <em>"Cofnij zgodę"</em>. System od razu blokuje
    dalsze wykorzystanie (np. nie wyśle kolejnego newsletteru tej osobie).
</p>

<h2>Żądania RODO</h2>
<p>
    Członek (lub jego opiekun) ma prawo do złożenia żądania:
</p>
<ul>
    <li><strong>Art. 15 (dostęp)</strong> — chce wiedzieć, jakie dane klub ma o nim.</li>
    <li><strong>Art. 16 (sprostowanie)</strong> — chce poprawić błędne dane.</li>
    <li><strong>Art. 17 (usunięcie, "right to be forgotten")</strong> — chce usunąć dane.</li>
    <li><strong>Art. 18 (ograniczenie)</strong> — chce, by klub przestał wykorzystywać dane
        bez ich usuwania.</li>
    <li><strong>Art. 20 (przenoszalność)</strong> — chce kopii w formacie CSV/JSON,
        by przenieść do innego klubu.</li>
</ul>

<p>
    Każde żądanie:
</p>
<ol>
    <li>Trafia do skrzynki sekretariatu (gdy klient pisze) lub jest wpisywane przez Ciebie.</li>
    <li>Trafia do administratora ochrony danych klubu (IOD) do weryfikacji.</li>
    <li>Klub ma 30 dni na realizację (z możliwością przedłużenia o kolejne 60 dni).</li>
    <li>Po realizacji wysyłasz potwierdzenie do osoby zgłaszającej.</li>
</ol>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Żądanie usunięcia (art. 17) <strong>nie zawsze</strong> musi być spełnione — klub
    może odmówić, gdy ma podstawę prawną do trzymania danych (np. faktury muszą
    być przechowywane 5 lat przez ustawę o rachunkowości). W razie wątpliwości
    kierujesz sprawę do IOD klubu.
</div>

<h2>Karta zgody — co dokładnie zostaje</h2>
<p>
    Każda zgoda zawiera: imię i nazwisko składającego, datę i godzinę,
    treść zgody (jaka wersja regulaminu), sposób wyrażenia (papier / e-zgoda / SMS),
    hash dokumentu, IP komputera (przy e-zgodzie). To pełna ścieżka dowodowa
    w razie sporu sądowego.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Raz w roku warto przeprowadzić <em>"audyt zgód"</em> — wyfiltrować osoby
    bez kompletu zgód i wysłać im przypomnienie o uzupełnieniu. To zwykle
    sprząta sytuacje, które uciekły uwadze przy migracji albo zmianach klauzul.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
