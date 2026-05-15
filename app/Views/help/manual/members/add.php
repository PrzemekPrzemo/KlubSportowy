<?php /** Dodanie nowego członka manualnie */ ?>
<p class="lead">Manualne dodanie członka to najszybsza metoda dla pojedynczych rejestracji — np. gdy zawodnik dołącza w trakcie sezonu lub gdy przyjmujesz wnioski papierowe. Dla większej liczby osób użyj <a href="<?= url('help/admin-members-import') ?>">importu z CSV/Excel</a>.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Otwórz <strong>Członkowie → Lista</strong> i kliknij przycisk <em>+ Dodaj członka</em>.</li>
    <li>Wybierz typ konta: <em>Pełnoletni</em> (samodzielnie zarządza profilem) lub <em>Niepełnoletni</em> (wymaga opiekuna prawnego).</li>
    <li>Wypełnij dane podstawowe: imię, nazwisko, data urodzenia, płeć (do statystyk), e-mail kontaktowy.</li>
    <li>Dodaj telefon, adres zamieszkania, dane medyczne podstawowe (grupa krwi, alergie — opcjonalnie).</li>
    <li>Wybierz sekcję sportową i kategorię wiekową — system automatycznie zaproponuje stawkę składki.</li>
    <li>Dla niepełnoletnich: dodaj dane opiekuna (imię, nazwisko, e-mail, telefon) — utworzy się powiązane konto rodzica.</li>
    <li>Zaznacz wymagane zgody RODO i regulamin klubu (lista pojawia się dynamicznie).</li>
    <li>Kliknij <strong>Utwórz konto</strong>. Członek otrzyma e-mail z linkiem aktywacyjnym.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/members/new</div>
    <div class="manual-mockup-content">
        <h6 class="mb-3"><i class="bi bi-person-plus"></i> Nowy członek</h6>
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label small">Imię *</label><input class="form-control form-control-sm" value="Jan"></div>
            <div class="col-md-6"><label class="form-label small">Nazwisko *</label><input class="form-control form-control-sm" value="Kowalski"></div>
            <div class="col-md-4"><label class="form-label small">Data urodzenia *</label><input type="date" class="form-control form-control-sm" value="2010-03-15"></div>
            <div class="col-md-4"><label class="form-label small">Płeć</label><select class="form-select form-select-sm"><option>Mężczyzna</option></select></div>
            <div class="col-md-4"><label class="form-label small">PESEL</label><input class="form-control form-control-sm" value="10031512345"></div>
            <div class="col-md-6"><label class="form-label small">E-mail kontaktowy</label><input class="form-control form-control-sm" value="rodzic@example.com"></div>
            <div class="col-md-6"><label class="form-label small">Telefon</label><input class="form-control form-control-sm" value="+48 600 100 200"></div>
            <div class="col-md-6"><label class="form-label small">Sekcja sportowa *</label><select class="form-select form-select-sm"><option>Piłka nożna — Juniorzy</option></select></div>
            <div class="col-md-6"><label class="form-label small">Kategoria</label><select class="form-select form-select-sm"><option>U-16</option></select></div>
        </div>
        <div class="form-check mt-3"><input class="form-check-input" type="checkbox" checked><label class="form-check-label small">Wyrażam zgodę na przetwarzanie danych osobowych zgodnie z RODO</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" checked><label class="form-check-label small">Akceptuję regulamin klubu</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox"><label class="form-check-label small">Zgoda marketingowa (opcjonalnie)</label></div>
        <button class="btn btn-primary mt-3"><i class="bi bi-check-lg"></i> Utwórz konto</button>
        <button class="btn btn-outline-secondary mt-3">Anuluj</button>
    </div>
    <div class="manual-mockup-caption">Formularz dodania nowego członka z dynamicznymi polami zgód.</div>
</div>

<h2>Wymagane vs. opcjonalne</h2>
<p>Wymagane są jedynie: imię, nazwisko, data urodzenia oraz sekcja sportowa. Pozostałe dane można uzupełnić później przez edycję profilu lub przez portal członka (zawodnik sam wprowadzi telefon i adres po pierwszym logowaniu).</p>

<div class="manual-callout manual-callout-warn">
    <strong>PESEL i RODO.</strong> Zbieraj PESEL tylko gdy jest to faktycznie niezbędne (np. dla licencji federacyjnej). Zasada minimalizacji danych z art. 5 RODO obowiązuje również kluby sportowe.
</div>

<h2>Co się dzieje po utworzeniu konta</h2>
<ul>
    <li>Na e-mail członka (lub opiekuna w przypadku osoby niepełnoletniej) trafia link aktywacyjny ważny 14 dni.</li>
    <li>System automatycznie tworzy harmonogram składek zgodnie z konfiguracją sekcji.</li>
    <li>Trener przypisany do sekcji otrzymuje powiadomienie o nowym zawodniku.</li>
    <li>W audit logu pojawia się wpis <code>member.created</code> z Twoim ID.</li>
</ul>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details><summary>Co jeśli członek nie ma e-maila?</summary><div class="faq-body">Możesz zaznaczyć <em>Brak e-maila</em> — wtedy login zostanie wygenerowany jako <code>imie.nazwisko@klub.local</code>, a hasło wręczysz osobiście. Komunikacja przez ClubDesk będzie ograniczona.</div></details>
    <details><summary>Jak dodać dziecko z dwoma opiekunami?</summary><div class="faq-body">Po utworzeniu konta wejdź w profil dziecka → zakładka <em>Opiekunowie</em> i kliknij <em>Dodaj kolejnego opiekuna</em>. Drugi rodzic otrzyma osobne zaproszenie.</div></details>
    <details><summary>Czy mogę dodać zawodnika bez sekcji?</summary><div class="faq-body">Nie — sekcja jest wymagana. Jeśli zawodnik dopiero podejmuje decyzję, przypisz go do sekcji <em>Tymczasowa / wstępna</em>.</div></details>
</div>
