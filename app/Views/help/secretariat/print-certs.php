<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Drukowanie zaświadczeń (PDF)</h1>
<p class="lead">
    Zaświadczenia to dokumenty potwierdzające jakiś stan faktyczny: członkostwo
    w klubie, przebyte szkolenia, opłatę składki, ukończenie kursu. ClubDesk
    generuje je z szablonów klubu jednym kliknięciem.
</p>

<h2>Typy zaświadczeń</h2>
<ul>
    <li><strong>Zaświadczenie o przynależności do klubu</strong> — wymagane do szkoły,
        do uczelni, do urzędu skarbowego (ulgi).</li>
    <li><strong>Zaświadczenie o opłaconej składce</strong> — często do firmy
        sponsorującej dziecko (refundacja).</li>
    <li><strong>Zaświadczenie o ukończeniu szkolenia</strong> — np. obozu sportowego.</li>
    <li><strong>Zaświadczenie o reprezentacji</strong> — startował w turnieju
        rangi krajowej (uznawane do indeksu).</li>
</ul>

<h2>Wystawienie zaświadczenia</h2>
<p>
    Najszybsza ścieżka:
</p>
<ol>
    <li>Wchodzisz w kartę członka → zakładka <em>Zaświadczenia</em>.</li>
    <li>Klikasz <strong>+ Wystaw zaświadczenie</strong>.</li>
    <li>Wybierasz typ z listy.</li>
    <li>Sprawdzasz dane — system wypełnia automatycznie z karty członka.</li>
    <li>Klikasz <em>Wygeneruj PDF</em>.</li>
    <li>PDF otwiera się w nowej karcie — możesz wydrukować lub wysłać e-mailem.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/members/147/certificates/new</div>
    <div class="manual-mockup-content">
        <h6>Wystaw zaświadczenie — Antoni Kowalski</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Typ zaświadczenia</label>
                <select class="form-select" disabled>
                    <option selected>Zaświadczenie o przynależności do klubu</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Cel</label>
                <input class="form-control" value="Szkoła Podstawowa nr 5 — zwolnienie z WF" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Okres przynależności</label>
                <input class="form-control" value="od 2024-09-01 do nadal" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Numer zaświadczenia</label>
                <input class="form-control" value="Z-2026/05/078 (auto)" disabled>
            </div>
        </div>
        <hr>
        <h6 class="mt-3">Podpis</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="radio" class="form-check-input" checked disabled>
                    <label class="form-check-label">Pieczątka klubu + ręczny podpis (drukuję)</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-check-input" disabled>
                    <label class="form-check-label">Podpis elektroniczny (eIDAS)</label>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 justify-content-end mt-3">
            <button class="btn btn-outline-secondary"><i class="bi bi-eye"></i> Podgląd PDF</button>
            <button class="btn btn-primary">Wygeneruj i zapisz w teczce</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: formularz wystawiania zaświadczenia.</div>
</div>

<h2>Numeracja zaświadczeń</h2>
<p>
    Każde zaświadczenie ma unikalny numer (np. <code>Z-2026/05/078</code>) — to
    pozwala szkole/urzędowi zweryfikować autentyczność (klubowi można zadzwonić
    i podać numer; sekretariat sprawdzi go w systemie).
</p>

<h2>Podpis: papierowy czy elektroniczny</h2>
<ul>
    <li><strong>Papierowy</strong> (domyślny) — drukujesz PDF, dyrektor klubu
        podpisuje, ksero do wglądu w teczce. Najczęstsze.</li>
    <li><strong>Elektroniczny eIDAS</strong> — wymaga kwalifikowanego podpisu
        zarządu. Dokument akceptowany przez urzędy bez wydruku.</li>
</ul>

<h2>Wysyłka e-mailem</h2>
<p>
    Po wygenerowaniu PDF klikasz <em>"Wyślij e-mailem"</em> — wiadomość trafia
    na adres rodzica (lub samego członka, jeśli pełnoletni). PDF jest
    automatycznie zarchiwizowany w teczce członka — w razie utraty rodzic
    może go pobrać z aplikacji bez konieczności proszenia ponownie.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Niektóre zaświadczenia (np. o reprezentacji) wymagają <strong>weryfikacji
    faktów</strong> przez zarząd lub trenera — np. potwierdzenia, że zawodnik
    rzeczywiście startował. System nie pozwoli zarządowi wystawić takiego
    zaświadczenia bez weryfikacji w bazie wyników.
</div>

<h2>Wystawianie hurtowe</h2>
<p>
    Pod koniec sezonu często trzeba wystawić tę samą "zaświadczenie o
    przynależności w sezonie X" dla całej sekcji. Akcja
    <em>Sekcje → Wystaw zaświadczenie grupowe</em> generuje paczkę PDF-ów (jeden
    plik na osobę) i wysyła automatycznie e-maile.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Jeżeli klub wystawia dużo zaświadczeń o opłaconej składce (do firm
    refundujących), warto raz w semestrze zorganizować <em>"falę"</em> i
    wystawić wszystkim hurtowo — to dwa kliknięcia, a oszczędza godziny pracy
    w rozproszonych prośbach.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
