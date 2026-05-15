<?php
$page = [
    'title'        => 'Moje dane osobowe',
    'category'     => 'Zawodnik',
    'group'        => 'Mój profil',
    'last_updated' => '2026-05-15',
    'reading_time' => '4 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Moje dane osobowe</h1>
<p class="lead">Profil to Twoja wizytówka w klubie. Tu sprawdzisz i zaktualizujesz wszystko, co klub o Tobie wie: imię, adres, telefon kontaktowy, datę urodzenia, kontakt awaryjny. Większość pól możesz zmienić sam(a) — bez pisania do sekretariatu.</p>

<h2>Jak dostać się do profilu</h2>
<ol>
    <li>Zaloguj się do portalu.</li>
    <li>W górnej belce kliknij <strong>Profil</strong> (ikonka osoby).</li>
    <li>Zobaczysz wszystkie swoje dane podzielone na sekcje.</li>
</ol>

<h2>Co znajdziesz w profilu</h2>
<ul>
    <li><strong>Dane podstawowe</strong> — imię, nazwisko, data urodzenia, płeć, PESEL (jeśli wymagany przez federację).</li>
    <li><strong>Kontakt</strong> — e-mail, telefon, adres zamieszkania.</li>
    <li><strong>Kontakt awaryjny</strong> — kto i jak ma Cię znaleźć, gdyby na treningu coś się stało.</li>
    <li><strong>Dane sportowe</strong> — kategoria wagowa, wzrost, dominująca ręka itp. (zależy od dyscypliny).</li>
    <li><strong>Dokumenty</strong> — umowy, oświadczenia, badania (opis na osobnej stronie manualu).</li>
    <li><strong>Bezpieczeństwo</strong> — zmiana hasła, 2FA.</li>
</ul>

<h2>Edycja danych — krok po kroku</h2>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź w <em>Profil</em>.</li>
    <li><span class="manual-step-num">2</span>Kliknij przycisk <strong>Edytuj</strong> w prawym górnym rogu sekcji, którą chcesz zmienić.</li>
    <li><span class="manual-step-num">3</span>Popraw dane w formularzu — pola z czerwoną gwiazdką są obowiązkowe.</li>
    <li><span class="manual-step-num">4</span>Kliknij <strong>Zapisz zmiany</strong>. Zmiany są widoczne od razu.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/profile</div>
    <div class="manual-mockup-content">
        <h5 class="mb-3"><i class="bi bi-person-circle text-primary"></i> Mój profil</h5>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Dane podstawowe</h6>
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edytuj</button>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6"><small class="text-muted">Imię i nazwisko</small><div>Anna Kowalska</div></div>
                    <div class="col-md-6"><small class="text-muted">Data urodzenia</small><div>14.03.2010</div></div>
                    <div class="col-md-6 mt-2"><small class="text-muted">E-mail</small><div>anna.kowalska@example.com</div></div>
                    <div class="col-md-6 mt-2"><small class="text-muted">Telefon</small><div>+48 600 100 200</div></div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-3">Kontakt awaryjny</h6>
                <div class="row">
                    <div class="col-md-6"><small class="text-muted">Osoba</small><div>Maria Kowalska (mama)</div></div>
                    <div class="col-md-6"><small class="text-muted">Telefon</small><div>+48 601 200 300</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Profil zawodnika podzielony jest na czytelne karty — możesz edytować każdą z osobna.</div>
</div>

<h2>Kto widzi moje dane?</h2>
<p>Domyślnie Twoje dane widzą tylko: Ty, Twój trener i administracja klubu. Możesz dodatkowo włączyć <em>publiczny profil zawodnika</em> (z osiągnięciami) — to robisz w sekcji <em>Profil → Prywatność</em>. Bez tej zgody nikt z zewnątrz Cię nie zobaczy.</p>

<div class="manual-info">
    <strong>Co z PESEL-em?</strong> PESEL jest wymagany tylko gdy klub zgłasza Cię do federacji sportowej (Związek Sportowy). Jeśli go nie potrzebują — pole zostaw puste, system tego nie blokuje.
</div>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Zmieniłem(am) numer telefonu — co dalej?</summary>
    <p>Wpisz nowy w profilu i kliknij Zapisz. Klub automatycznie zobaczy aktualizację. Powiadomienia SMS będą wysyłane już na nowy numer.</p>
</details>
<details>
    <summary>Nie mogę edytować swojego imienia / PESEL-u</summary>
    <p>To pole zablokowane do edycji samodzielnej, bo musi zgadzać się z dokumentami i federacją. Napisz do sekretariatu klubu — oni je poprawią.</p>
</details>
<details>
    <summary>Czy mogę usunąć stary kontakt awaryjny?</summary>
    <p>Tak. W sekcji Kontakt awaryjny kliknij Edytuj, usuń wpis lub dodaj nowy. Możesz mieć kilka kontaktów jednocześnie.</p>
</details>
