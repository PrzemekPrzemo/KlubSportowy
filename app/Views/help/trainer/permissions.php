<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Co widzi i edytuje trener — uprawnienia</h1>
<p class="lead">
    Zakres widoczności i edycji w panelu trenera nie jest stały — to konfiguracja
    klubu. Niemniej domyślne ustawienia ClubDesk są bardzo zbliżone w większości
    organizacji i tym właśnie zajmiemy się w tym rozdziale.
</p>

<h2>Co trener może podejrzeć</h2>
<p>
    Trener ma <strong>pełen wgląd</strong> w dane sportowe swoich zawodników:
    historię obecności, postępy, statystyki, wyniki turniejowe, ranking sezonowy.
    Widzi również <em>dane podstawowe</em>: imię, nazwisko, datę urodzenia,
    kontakt do rodzica/opiekuna, fotografię w paszporcie sportowym.
</p>

<p>
    W zakresie zdrowia trener widzi <strong>termin ważności badań</strong> (np.
    "ważne do 2026-08-30"), informację o aktywnych zwolnieniach z treningu i
    ogólne ograniczenia (np. "wykluczone gry kontaktowe przez 2 tygodnie"). Trener
    <em>nie ma dostępu</em> do diagnozy medycznej, wyników krwi, danych z konsultacji
    z lekarzem klubowym.
</p>

<h2>Co trener może edytować</h2>
<p>Trener może w pełni zarządzać:</p>
<ul>
    <li>Obecnościami zawodników na własnych treningach.</li>
    <li>Notatkami trenerskimi (publicznymi i prywatnymi).</li>
    <li>Składami zawodników na turnieje i mecze.</li>
    <li>Wpisywaniem wyników indywidualnych i drużynowych.</li>
    <li>Wiadomościami do rodziców i zawodników w swojej sekcji.</li>
</ul>

<p>Trener <strong>nie może</strong>:</p>
<ul>
    <li>Dodawać ani usuwać członków klubu (robi to sekretariat).</li>
    <li>Wystawiać faktur i ingerować w finanse.</li>
    <li>Modyfikować swoich własnych stawek prowizji.</li>
    <li>Przenosić zawodnika do innej sekcji bez zgody zarządu.</li>
    <li>Widzieć danych zawodników z innych sekcji.</li>
</ul>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/admin/roles/trainer</div>
    <div class="manual-mockup-content">
        <h6>Macierz uprawnień — rola: trener</h6>
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr><th>Moduł</th><th class="text-center">Podgląd</th><th class="text-center">Edycja</th></tr>
            </thead>
            <tbody>
                <tr><td>Sekcje (moje)</td><td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td><td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td></tr>
                <tr><td>Sekcje (cudze)</td><td class="text-center"><i class="bi bi-x-circle text-muted"></i></td><td class="text-center"><i class="bi bi-x-circle text-muted"></i></td></tr>
                <tr><td>Zawodnicy — dane sportowe</td><td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td><td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td></tr>
                <tr><td>Zawodnicy — finanse</td><td class="text-center"><i class="bi bi-x-circle text-muted"></i></td><td class="text-center"><i class="bi bi-x-circle text-muted"></i></td></tr>
                <tr><td>Zawodnicy — badania (termin)</td><td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td><td class="text-center"><i class="bi bi-x-circle text-muted"></i></td></tr>
                <tr><td>Zawodnicy — diagnozy</td><td class="text-center"><i class="bi bi-x-circle text-muted"></i></td><td class="text-center"><i class="bi bi-x-circle text-muted"></i></td></tr>
                <tr><td>Turnieje — zgłoszenia, wyniki</td><td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td><td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td></tr>
                <tr><td>Prowizje (moje)</td><td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td><td class="text-center"><i class="bi bi-x-circle text-muted"></i></td></tr>
                <tr><td>Komunikacja z rodzicami</td><td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td><td class="text-center"><i class="bi bi-check-circle-fill text-success"></i></td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: macierz uprawnień trenera (zielona = dostęp, szara = brak).</div>
</div>

<h2>Indywidualne nadpisania</h2>
<p>
    Zarząd klubu może indywidualnie podnieść uprawnienia trenera-koordynatora,
    np. nadać mu prawo do <em>podglądu wszystkich sekcji</em> czy do
    <em>edytowania harmonogramu</em>. Takie wyjątki zapisywane są w tablicy
    <code>role_permissions_overrides</code> i są widoczne w Twoim profilu jako
    "Dodatkowe uprawnienia".
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Jeżeli klikniesz na funkcję, do której nie masz dostępu, zobaczysz komunikat
    <em>"Brak uprawnień do modułu"</em> z linkiem "Poproś o dostęp" — wiadomość
    trafi do zarządu klubu.
</div>

<h2>Ślad audytowy</h2>
<p>
    Wszystkie operacje, które wykonujesz w panelu (zaznaczenie obecności, wpis wyniku,
    zmiana składu) są zapisywane w <strong>logu audytowym</strong>. To pomaga
    rozstrzygać nieporozumienia ("kto skreślił zawodnika z listy zgłoszonych?") i
    jest wymagane przez RODO. Logu nie można edytować ani usuwać — nawet przez
    administratora.
</p>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
