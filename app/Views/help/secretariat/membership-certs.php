<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Zaświadczenia o przynależności</h1>
<p class="lead">
    Zaświadczenie o przynależności do klubu to dokument, którego rodzice
    najczęściej potrzebują dla szkoły (zwolnienie z WF, dopisanie do dziennika),
    dla urzędu skarbowego (ulga sportowa) albo dla pracodawcy (dziecko trenuje
    poważnie, prośba o elastyczność). Sekretariat wystawia go w 30 sekund.
</p>

<h2>Co zawiera zaświadczenie</h2>
<p>Standardowe zaświadczenie zawiera:</p>
<ul>
    <li>logo klubu i nagłówek (z numerem KRS / NIP);</li>
    <li>numer i datę zaświadczenia;</li>
    <li>imię, nazwisko, datę urodzenia członka;</li>
    <li>okres przynależności (od → do);</li>
    <li>sekcję i dyscyplinę;</li>
    <li>informację o uczestnictwie w treningach (liczbę godzin/tydzień);</li>
    <li>cel wystawienia (np. "dla szkoły", "dla urzędu skarbowego");</li>
    <li>podpis i pieczęć osoby uprawnionej.</li>
</ul>

<h2>Wystawianie pojedynczego</h2>
<p>
    Z karty członka klikasz <em>Zaświadczenia → + Wystaw zaświadczenie → Przynależność</em>.
    System wypełnia automatycznie dane członka i okres przynależności
    (od daty rejestracji do "nadal" lub do dnia wypisania).
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/members/147/certificates/membership</div>
    <div class="manual-mockup-content">
        <h6>Zaświadczenie o przynależności — Antoni Kowalski (147)</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Numer</label>
                <input class="form-control form-control-sm" value="Z-2026/05/078" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Data wystawienia</label>
                <input class="form-control form-control-sm" value="2026-05-15" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Okres przynależności</label>
                <input class="form-control form-control-sm" value="od 2024-09-01 do nadal" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Sekcja / dyscyplina</label>
                <input class="form-control form-control-sm" value="Skrzaty U-9 / piłka nożna" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Godziny treningowe / tyg.</label>
                <input class="form-control form-control-sm" value="4 godziny" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Cel wystawienia</label>
                <input class="form-control form-control-sm" value="Szkoła Podstawowa nr 5 — dziennik klasowy" disabled>
            </div>
            <div class="col-12">
                <label class="form-label small">Dodatkowa adnotacja (opcjonalna)</label>
                <textarea class="form-control form-control-sm" rows="2" disabled>Trenuje w klubie od 21 miesięcy. Regularna frekwencja 96%.</textarea>
            </div>
        </div>
        <hr>
        <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-outline-secondary"><i class="bi bi-eye"></i> Podgląd PDF</button>
            <button class="btn btn-primary">Wygeneruj i wyślij e-mail</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: formularz wystawiania zaświadczenia o przynależności.</div>
</div>

<h2>Wystawianie hurtowe (cała sekcja)</h2>
<p>
    Pod koniec sezonu (sierpień) szkoła często prosi o zaświadczenia dla
    wszystkich dzieci z klubu. Robisz to jednym kliknięciem:
</p>
<ol>
    <li>Wchodzisz w <strong>Sekcje → wybierz sekcję → Akcje → Zaświadczenia hurtowo</strong>.</li>
    <li>Wybierasz typ zaświadczenia (Przynależność) i wspólny cel
        ("Dla szkoły, rok 2026/27").</li>
    <li>System generuje paczkę PDF-ów — jeden plik na każdą osobę.</li>
    <li>Wybierasz: wysłać wszystkim e-mailem? Pobrać ZIP? Wydrukować na drukarce?</li>
</ol>

<h2>Cykl życia zaświadczenia</h2>
<p>
    Po wystawieniu zaświadczenie:
</p>
<ul>
    <li>Jest zapisane w teczce członka (zakładka "Zaświadczenia").</li>
    <li>Otrzymuje numer ciągły z rejestru klubu.</li>
    <li>Jest dostępne dla rodzica/członka w aplikacji (na zawsze, nie znika).</li>
    <li>Można je <em>unieważnić</em> (jeśli wystawiono błędnie) z wpisem do
        rejestru "anulowane" — nie można go usunąć fizycznie.</li>
</ul>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Zaświadczenie jest <strong>dokumentem o znaczeniu prawnym</strong> — jego
    wystawienie z nieprawdziwymi danymi (np. zawodnik tak naprawdę nigdy nie
    trenował) może być traktowane jako poświadczenie nieprawdy. System loguje
    każde wystawienie z Twoim podpisem. Wystawiaj świadomie.
</div>

<h2>Weryfikacja przez instytucję</h2>
<p>
    Każde zaświadczenie ma <strong>kod QR</strong> drukowany w prawym dolnym
    rogu PDF. Skanowanie tego kodu otwiera publiczny URL ClubDesk, gdzie
    instytucja może zweryfikować autentyczność: <em>"Tak, zaświadczenie nr
    Z-2026/05/078 zostało wystawione 15 maja 2026 przez Klub X dla osoby
    o inicjałach A.K."</em>. Pełne dane nie są ujawniane — tylko inicjały, dla
    RODO.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    W zaświadczeniach dla urzędu skarbowego warto dodać <em>"informacja o
    uiszczeniu składek w wysokości X zł w okresie Y–Z"</em> — to zwiększa
    szansę na uznanie ulgi sportowej. Pole "Dodatkowa adnotacja" służy
    właśnie temu.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
