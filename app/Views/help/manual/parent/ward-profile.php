<?php
$page = [
    'title'        => 'Profil dziecka — co widzę jako rodzic',
    'category'     => 'Rodzic',
    'group'        => 'Moje dziecko',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Profil dziecka</h1>
<p class="lead">Po wejściu w kartę podopiecznego masz pełen wgląd w jego dane — wszystko, co klub o nim wie. Możesz aktualizować dane kontaktowe, wgrywać dokumenty, sprawdzać status członkostwa i kontakty awaryjne. To Twoja kontrola nad tym, co system o dziecku przechowuje.</p>

<h2>Jak otworzyć profil</h2>
<ol>
    <li>Zaloguj się do portalu opiekuna.</li>
    <li>Na liście podopiecznych kliknij kafelek z dzieckiem.</li>
    <li>Wybierz zakładkę <strong>Profil</strong>.</li>
</ol>

<h2>Co znajdziesz w profilu dziecka</h2>
<ul>
    <li><strong>Dane podstawowe</strong> — imię, nazwisko, data urodzenia, PESEL, płeć.</li>
    <li><strong>Adres zamieszkania</strong> (zwykle Twój, jako opiekuna).</li>
    <li><strong>Kontakty awaryjne</strong> — Twój numer i dodatkowe osoby (drugi rodzic, dziadkowie).</li>
    <li><strong>Dane medyczne</strong> — alergie, choroby przewlekłe, leki.</li>
    <li><strong>Dane sportowe</strong> — wzrost, waga, kategoria wiekowa (zależnie od dyscypliny).</li>
    <li><strong>Dokumenty</strong> — zgody, badania lekarskie, oświadczenia.</li>
    <li><strong>Historia członkostwa</strong> — od kiedy dziecko jest w klubie.</li>
</ul>

<h2>Edycja danych</h2>
<p>Jako rodzic możesz edytować większość pól sam(a):</p>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź w profil dziecka.</li>
    <li><span class="manual-step-num">2</span>Przy sekcji, którą chcesz zmienić, kliknij <strong>Edytuj</strong>.</li>
    <li><span class="manual-step-num">3</span>Wprowadź nowe dane i kliknij <strong>Zapisz</strong>.</li>
    <li><span class="manual-step-num">4</span>Zmiany są widoczne natychmiast — klub też je zobaczy w swoim systemie.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/guardian/ward/142</div>
    <div class="manual-mockup-content">
        <div class="d-flex gap-3 align-items-start mb-3">
            <div style="width:80px;height:80px;border-radius:50%;background:#dee2e6;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-person fs-1"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-0">Anna Kowalska</h5>
                <small class="text-muted">UKS Iskra · Pływanie · członek od 09.2024</small>
                <div class="mt-1">
                    <span class="badge bg-success">Aktywna</span>
                    <span class="badge bg-info">Senior U18</span>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Dane podstawowe</h6>
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edytuj</button>
                </div>
                <hr>
                <div class="row small">
                    <div class="col-md-6"><span class="text-muted">Data urodzenia:</span> 14.03.2010</div>
                    <div class="col-md-6"><span class="text-muted">PESEL:</span> 10231405xxx</div>
                    <div class="col-md-6 mt-1"><span class="text-muted">Adres:</span> ul. Kwiatowa 5, Warszawa</div>
                    <div class="col-md-6 mt-1"><span class="text-muted">E-mail dziecka:</span> anna.k@example.com</div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h6>Dane medyczne</h6>
                <hr>
                <div class="small">
                    <div><span class="text-muted">Alergie:</span> brak</div>
                    <div><span class="text-muted">Leki:</span> brak</div>
                    <div><span class="text-muted">Wzrost:</span> 168 cm · <span class="text-muted">Waga:</span> 56 kg</div>
                    <div><span class="text-muted">Badania ważne do:</span> 27.05.2026 <span class="badge bg-warning text-dark">Wygasa wkrótce</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Profil dziecka z głównymi sekcjami — wszystko w jednym miejscu.</div>
</div>

<div class="manual-info">
    <strong>Co z PESEL i danymi wrażliwymi?</strong> PESEL jest wymagany przy zgłoszeniach do federacji sportowej. Dane medyczne (alergie, choroby) widzi tylko trener oraz lekarz klubowy — chronimy je zgodnie z RODO art. 9.
</div>

<h2>Pola zablokowane do edycji</h2>
<p>Niektórych pól nie możesz zmienić sam(a) — np. imienia, nazwiska, PESEL-u. To wymaga papierowego wniosku w sekretariacie, bo wpływa na dokumenty rejestracyjne i zgłoszenia do federacji. Klikając w takie pole zobaczysz instrukcję, jak je zaktualizować.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Dziecko zmieniło adres szkolny — czy aktualizować profil?</summary>
    <p>Jeśli to chwilowa zmiana (obozy, wakacje) — nie musisz. Jeśli przeprowadzka — tak, zaktualizuj. Wpływa to na poprawność dokumentów i wysyłkę powiadomień.</p>
</details>
<details>
    <summary>Co z numerem telefonu — dziecko ma swój</summary>
    <p>Możesz wpisać telefon dziecka jako dodatkowy. Klub używa go w drugiej kolejności po Twoim — np. gdy dziecko jest na obozie i klub szuka kontaktu pilnie.</p>
</details>
<details>
    <summary>Dziecko skończyło 18 lat — co dalej?</summary>
    <p>System automatycznie przekształca konto: dziecko przejmuje pełną kontrolę nad swoim profilem. Twoje konto opiekuna zachowuje historyczny wgląd, ale nie może już zmieniać danych.</p>
</details>
