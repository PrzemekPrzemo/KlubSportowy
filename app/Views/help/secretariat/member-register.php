<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Rejestracja nowego członka klubu</h1>
<p class="lead">
    Rejestracja nowego członka to najczęstsza operacja sekretariatu — wykonywana
    kilka–kilkanaście razy w tygodniu (we wrześniu nawet kilkadziesiąt razy
    dziennie). ClubDesk dzieli proces na trzy logiczne etapy, by minimalizować
    ryzyko błędu.
</p>

<h2>Trzy etapy rejestracji</h2>
<ol>
    <li><strong>Dane podstawowe</strong> — imię, nazwisko, PESEL, kontakt.</li>
    <li><strong>Sport i sekcja</strong> — wybór dyscypliny i grupy treningowej.</li>
    <li><strong>Dokumenty i zgody</strong> — RODO, badania, regulamin.</li>
</ol>

<h2>Etap 1: Dane podstawowe</h2>
<p>
    W menu klikasz <strong>Członkowie → + Nowy członek</strong>. Otwiera się
    pierwsza strona formularza. Pola obowiązkowe są oznaczone gwiazdką. ClubDesk
    automatycznie weryfikuje PESEL (suma kontrolna) i sprawdza, czy taka osoba
    nie jest już zapisana (po PESEL lub kombinacji imię + nazwisko + data
    urodzenia).
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/members/new?step=1</div>
    <div class="manual-mockup-content">
        <h6>Nowy członek — krok 1/3: Dane podstawowe</h6>
        <div class="progress mb-3" style="height:6px;"><div class="progress-bar bg-primary" style="width:33%"></div></div>
        <form>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Imię *</label>
                    <input type="text" class="form-control" value="Iza" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nazwisko *</label>
                    <input type="text" class="form-control" value="Pawlak" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">PESEL *</label>
                    <input type="text" class="form-control" value="17240301***" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Data urodzenia (auto)</label>
                    <input type="date" class="form-control" value="2017-04-03" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail kontaktowy</label>
                    <input type="email" class="form-control" value="rodzice@example.pl" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input type="tel" class="form-control" value="+48 600 ***" disabled>
                </div>
                <div class="col-12">
                    <label class="form-label">Opiekun prawny (dla niepełnoletnich)</label>
                    <input type="text" class="form-control" value="Iza Pawlak (ojciec)" disabled>
                </div>
            </div>
            <div class="alert alert-info small mt-3"><i class="bi bi-check-circle"></i> PESEL poprawny. W bazie klubu nie znaleziono duplikatu.</div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <button class="btn btn-outline-secondary">Anuluj</button>
                <button class="btn btn-primary">Dalej <i class="bi bi-arrow-right"></i></button>
            </div>
        </form>
    </div>
    <div class="manual-mockup-caption">Mockup: krok 1 formularza rejestracji — dane osobowe z walidacją PESEL.</div>
</div>

<h2>Etap 2: Sport i sekcja</h2>
<p>
    W kroku drugim wybierasz <strong>dyscyplinę</strong> (np. piłka nożna, judo)
    i <strong>sekcję</strong> (konkretną grupę treningową). System pokazuje
    tylko aktywne sekcje pasujące do wieku zawodnika. Możesz też zaznaczyć "do
    wstępnej kwalifikacji" — wtedy zawodnik trafia do "puli oczekujących" i
    trener decyduje o przypisaniu po pierwszym treningu próbnym.
</p>

<h2>Etap 3: Dokumenty i zgody</h2>
<p>
    Najważniejszy etap pod kątem RODO. Zaznaczasz:
</p>
<ul>
    <li><strong>Zgoda RODO</strong> — wymagana, w innym wypadku system nie pozwoli zakończyć rejestracji.</li>
    <li><strong>Zgoda na wizerunek</strong> — opcjonalna, dotyczy zdjęć z treningów.</li>
    <li><strong>Zgoda na newsletter</strong> — opcjonalna.</li>
    <li><strong>Akceptacja regulaminu klubu</strong> — wymagana.</li>
</ul>

<p>
    Dla niepełnoletnich zgody podpisuje rodzic. Możesz wprowadzić papierowy
    dokument w trakcie wizyty rodzica w sekretariacie albo wysłać <em>e-zgody</em>
    — rodzic dostaje link e-mailem, podpisuje cyfrowo (jednorazowy kod SMS) i
    proces dokończy się sam.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Bez kompletu zgód RODO i regulaminu zawodnik <strong>nie zostanie
    zaakceptowany na trening</strong> — trener zobaczy go z czerwoną ikoną.
    Dlatego dążymy do zakończenia pełnej rejestracji w trakcie wizyty rodzica.
</div>

<h2>Po rejestracji</h2>
<p>
    Po zatwierdzeniu wszystkich trzech kroków:
</p>
<ul>
    <li>Nowy członek pojawia się w bazie klubu.</li>
    <li>Trener docelowej sekcji dostaje powiadomienie ("nowy zawodnik w sekcji").</li>
    <li>Rodzic dostaje e-mail powitalny z linkami do aplikacji PWA i polityki klubu.</li>
    <li>Pierwsza faktura zostaje wygenerowana w nocy z 1-szego dnia kolejnego miesiąca.</li>
</ul>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Możesz wstrzymać rejestrację w połowie i wrócić do niej później — system
    zapisze stan jako "robocza". Lista wersji roboczych jest dostępna w
    <em>Członkowie → Robocze</em>.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
