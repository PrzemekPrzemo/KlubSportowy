<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Dokumenty członka — umowy, oświadczenia, RODO</h1>
<p class="lead">
    Każdy aktywny członek klubu ma teczkę dokumentów: umowę o członkostwo,
    zgody RODO, oświadczenie rodzica, regulamin, ewentualnie umowę stypendialną.
    ClubDesk pomaga zarządzać tym wszystkim cyfrowo — bez papierowych szafek.
</p>

<h2>Otwarcie teczki członka</h2>
<p>
    W karcie członka przechodzisz na zakładkę <strong>Dokumenty</strong>.
    Zobaczysz listę plików pogrupowanych według typu: <em>Umowa</em>, <em>Zgody
    RODO</em>, <em>Badania medyczne</em>, <em>Zaświadczenia</em>, <em>Inne</em>.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/members/147/documents</div>
    <div class="manual-mockup-content">
        <h6>Dokumenty: Antoni Kowalski (147)</h6>
        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-sm btn-primary"><i class="bi bi-cloud-upload"></i> Wgraj plik</button>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-text"></i> Wygeneruj z szablonu</button>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-send"></i> Wyślij e-zgodę</button>
        </div>
        <table class="table table-sm">
            <thead class="table-light">
                <tr><th>Typ</th><th>Plik</th><th>Data</th><th>Status</th><th>Akcje</th></tr>
            </thead>
            <tbody>
                <tr><td><span class="badge bg-primary">Umowa</span></td><td>umowa_2024_09_kowalski.pdf</td><td>2024-09-01</td><td><span class="badge bg-success">Podpisana</span></td><td><i class="bi bi-eye"></i> <i class="bi bi-download"></i></td></tr>
                <tr><td><span class="badge bg-info">RODO</span></td><td>zgoda_rodo_kowalski.pdf</td><td>2024-09-01</td><td><span class="badge bg-success">Podpisana e-zgodą</span></td><td><i class="bi bi-eye"></i> <i class="bi bi-download"></i></td></tr>
                <tr><td><span class="badge bg-info">RODO wizerunek</span></td><td>zgoda_wizerunek_kowalski.pdf</td><td>2024-09-01</td><td><span class="badge bg-success">Tak</span></td><td><i class="bi bi-eye"></i></td></tr>
                <tr><td><span class="badge bg-danger">Badanie</span></td><td>badanie_2025_09_kowalski.pdf</td><td>2025-09-12</td><td><span class="badge bg-success">Ważne do 2026-09-12</span></td><td><i class="bi bi-eye"></i></td></tr>
                <tr><td><span class="badge bg-secondary">Inne</span></td><td>oswiadczenie_szczepien.pdf</td><td>2024-09-01</td><td>—</td><td><i class="bi bi-eye"></i></td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: teczka dokumentów członka z typami i statusami.</div>
</div>

<h2>Wgrywanie plików</h2>
<p>
    Akcja <strong>Wgraj plik</strong> otwiera okno wyboru. Dopuszczalne formaty:
    PDF, JPG, PNG. Maksymalny rozmiar: 10 MB na plik. Po wgraniu wybierasz typ
    (z listy słownikowej) i opcjonalnie datę dokumentu i datę wygaśnięcia.
</p>

<h2>Generowanie z szablonu</h2>
<p>
    Zamiast wgrywać gotowy plik, możesz <strong>wygenerować dokument z szablonu</strong>
    klubu. ClubDesk ma kilka szablonów predefiniowanych:
</p>
<ul>
    <li>Umowa o członkostwo (dorosły / niepełnoletni).</li>
    <li>Zgoda RODO (przetwarzanie / wizerunek / newsletter).</li>
    <li>Oświadczenie o stanie zdrowia.</li>
    <li>Zaświadczenie o przynależności do klubu.</li>
</ul>
<p>
    Wszystkie szablony są dwujęzyczne (PL/EN, opcjonalnie). Dane członka są
    automatycznie wstawiane w odpowiednie miejsca. Po wygenerowaniu wybierasz —
    drukować i podpisać ręcznie, czy wysłać do podpisania e-zgodą.
</p>

<h2>E-zgody</h2>
<p>
    E-zgoda to mechanizm <strong>zdalnego podpisywania</strong> dokumentów.
    Rodzic dostaje e-mail z linkiem; klika → otwiera się dokument w przeglądarce;
    podpisuje przez wprowadzenie kodu SMS wysłanego na jego numer; gotowe.
    Dokument zostaje zapisany w teczce ze statusem "Podpisana e-zgodą",
    znacznikiem czasu i odciskiem (hashem) podpisu.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga prawna:</strong>
    E-zgoda spełnia wymogi RODO, ale niektóre dokumenty (np. umowa
    stypendialna z pełnoletnim zawodnikiem) wymagają papierowego podpisu w
    Polsce. W razie wątpliwości skonsultuj z prawnikiem klubu.
</div>

<h2>Wygaśnięcie i przypomnienia</h2>
<p>
    Dokumenty z datą wygaśnięcia (badania, polisa NNW, licencja związkowa) są
    automatycznie monitorowane. Na <strong>30 dni</strong> przed datą system
    pokazuje ostrzeżenie w teczce członka i dodaje wpis do dashboardu
    sekretariatu ("X osób z dokumentami wygasającymi w ciągu 30 dni").
</p>

<h2>Retencja i RODO art. 17</h2>
<p>
    Po zakończeniu członkostwa dokumenty są przechowywane przez okres retencji
    klubu (domyślnie 5 lat). Po tym okresie są automatycznie usuwane. Były
    członek może wcześniej zażądać usunięcia (RODO art. 17) — wniosek trafia
    do administratora, sekretariat tego sam nie wykonuje.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Filtrowanie listy członków z <em>brakującymi dokumentami</em> (Członkowie →
    Filtr → "Brak ważnych badań" lub "Brak zgody RODO") to świetny sposób na
    zachowanie porządku — wystarczy 5 minut tygodniowo, by mieć wszystko
    "na czas".
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
