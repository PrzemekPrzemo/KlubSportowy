<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Profil zawodnika — perspektywa trenera</h1>
<p class="lead">
    Profil zawodnika to centrum informacji o jednym ćwiczącym. Trener widzi
    wszystko, co jest mu potrzebne do prowadzenia treningu, ale nie ma dostępu do
    danych finansowych ani diagnoz medycznych.
</p>

<h2>Struktura profilu</h2>
<p>Profil podzielony jest na pięć sekcji:</p>
<ul>
    <li><strong>Dane podstawowe</strong> — imię, nazwisko, data urodzenia, numer licencji.</li>
    <li><strong>Kontakt</strong> — telefon rodzica/opiekuna, e-mail, adres.</li>
    <li><strong>Badania i zdrowie</strong> — data ważności badań, aktywne ograniczenia.</li>
    <li><strong>Postępy</strong> — obecności, statystyki, wyniki turniejowe.</li>
    <li><strong>Notatki trenerskie</strong> — Twoje prywatne uwagi (zob. niżej).</li>
</ul>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/athletes/antoni-kowalski</div>
    <div class="manual-mockup-content">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width:64px; height:64px;">
                <i class="bi bi-person-fill fs-3 text-secondary"></i>
            </div>
            <div>
                <h5 class="mb-0">Antoni Kowalski</h5>
                <small class="text-muted">Skrzaty U-9 · rocznik 2016 · członek od 2024-09</small>
            </div>
            <div class="ms-auto">
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-envelope"></i> Wiadomość</button>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-telephone"></i> Zadzwoń</button>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Kontakt opiekuna</h6>
                        <div><i class="bi bi-person"></i> Marta Kowalska (mama)</div>
                        <div><i class="bi bi-telephone"></i> +48 600 *** ***</div>
                        <div><i class="bi bi-envelope"></i> m.kowalska@****.pl</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Badania</h6>
                        <div>Ważność: <strong>2026-09-12</strong> <span class="badge bg-success">OK</span></div>
                        <div class="text-muted small">Brak aktywnych ograniczeń.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Frekwencja (sezon)</h6>
                        <div class="h4 mb-0">96%</div>
                        <small class="text-muted">39 z 41 zaplanowanych treningów</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Turnieje</h6>
                        <div>Rozegranych: <strong>5</strong></div>
                        <div>Bramki / asysty: <strong>3 / 2</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: profil zawodnika z perspektywy trenera. Numery telefonu częściowo zamaskowane do screenshotów.</div>
</div>

<h2>Notatki trenerskie</h2>
<p>
    To miejsce, w którym możesz zapisać obserwacje rozwojowe — czy to o technice,
    czy o postawie. Notatki dzielą się na dwa typy:
</p>
<ul>
    <li><strong>Publiczne</strong> — widoczne dla innych trenerów Twojej sekcji
        i zarządu klubu (np. "Antek znacznie poprawił lewą nogę przez ostatni
        miesiąc — proponuję rotacyjnie wpisać go w skład").</li>
    <li><strong>Prywatne</strong> — widoczne tylko dla Ciebie (np. "rozmowa z mamą
        Antka, dziecko zestresowane szkołą — odpuścić presję na ten miesiąc").</li>
</ul>

<p>
    Notatki <em>nie są</em> widoczne dla zawodnika ani rodzica. Mają charakter
    roboczy. Jeśli chcesz przekazać uwagę rodzicowi — użyj modułu wiadomości
    (rozdział o komunikacji).
</p>

<h2>Czego trener nie zobaczy</h2>
<p>
    W profilu <strong>nie znajdziesz</strong>:
</p>
<ul>
    <li>statusu płatności składek (kto ma zaległości — to ma sekretariat);</li>
    <li>diagnozy lekarskiej (tylko data ważności, ewentualnie ogólne ograniczenia);</li>
    <li>danych finansowych rodziny (dochody, ulgi).</li>
</ul>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Karta zawodnika ma w prawym górnym rogu przycisk <em>"Drukuj paszport
    sportowy"</em> — generuje 1-stronicowy PDF z numerem licencji, zdjęciem,
    danymi opiekuna i kontaktem awaryjnym. Przydaje się na zawodach.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
