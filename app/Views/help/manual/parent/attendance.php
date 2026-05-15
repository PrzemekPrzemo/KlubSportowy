<?php
$page = [
    'title'        => 'Obecność dziecka',
    'category'     => 'Rodzic',
    'group'        => 'Aktywności',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Obecność dziecka</h1>
<p class="lead">Frekwencja to jeden z najbardziej praktycznych ekranów portalu opiekuna — pozwala szybko sprawdzić, czy dziecko regularnie chodzi na treningi. Wiele klubów łączy obecność z innymi rzeczami: zniżkami na składkach, kwalifikacjami do zawodów, decyzjami trenera. Jako rodzic widzisz dokładnie to samo, co dziecko — plus możliwość usprawiedliwiania nieobecności.</p>

<h2>Gdzie znaleźć obecność</h2>
<p>Wejdź w profil dziecka → zakładka <strong>Obecność</strong>. Zobaczysz duży licznik za bieżący miesiąc i historię ostatnich treningów.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/guardian/ward/142/attendance</div>
    <div class="manual-mockup-content">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <small class="text-muted">Maj 2026</small>
                        <h3 class="mb-0 text-success">88%</h3>
                        <small>7 z 8 treningów</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <small class="text-muted">3 ostatnie miesiące</small>
                        <h3 class="mb-0 text-primary">82%</h3>
                        <small>20 z 24 treningów</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <small class="text-muted">Sezon 2025/26</small>
                        <h3 class="mb-0">79%</h3>
                        <small>91 z 115 treningów</small>
                    </div>
                </div>
            </div>
        </div>
        <h6>Ostatnie treningi</h6>
        <table class="table table-sm align-middle">
            <thead><tr><th>Data</th><th>Trening</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <tr><td>22.05</td><td>Pływanie · 17:00</td><td><span class="badge bg-success">Obecność</span></td><td></td></tr>
                <tr><td>20.05</td><td>Pływanie · 17:00</td><td><span class="badge bg-success">Obecność</span></td><td></td></tr>
                <tr><td>18.05</td><td>Pływanie · 17:00</td><td><span class="badge bg-warning text-dark">Nieob. (dentysta)</span></td><td></td></tr>
                <tr><td>17.05</td><td>Pływanie · 17:00</td><td><span class="badge bg-danger">Nieobecność</span></td><td><button class="btn btn-sm btn-link">Usprawiedliw</button></td></tr>
                <tr><td>15.05</td><td>Pływanie · 17:00</td><td><span class="badge bg-success">Obecność</span></td><td></td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Pełen wgląd w obecność dziecka, z opcją usprawiedliwienia.</div>
</div>

<h2>Co oznaczają kolory</h2>
<ul>
    <li><span class="badge bg-success">Obecność</span> — dziecko było na treningu, trener potwierdził.</li>
    <li><span class="badge bg-warning text-dark">Nieobecność usprawiedliwiona</span> — zgłosiłeś(aś) wcześniej powód (choroba, szkoła, dentysta).</li>
    <li><span class="badge bg-danger">Nieobecność</span> — dziecko nie przyszło bez powodu, wpływa na frekwencję.</li>
    <li><span class="badge bg-secondary">Spóźnienie</span> — przyszło, ale po rozpoczęciu.</li>
</ul>

<h2>Usprawiedliwianie nieobecności</h2>
<p>Z poziomu rodzica możesz usprawiedliwić zarówno przyszłą (zaplanowaną) nieobecność, jak i wsteczną (zapomniałeś zgłosić, że dziecko było chore):</p>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź w Obecność dziecka.</li>
    <li><span class="manual-step-num">2</span>Znajdź konkretny trening (przyszły lub miniony do 7 dni wstecz).</li>
    <li><span class="manual-step-num">3</span>Kliknij <strong>Usprawiedliw</strong>.</li>
    <li><span class="manual-step-num">4</span>Podaj krótko powód (choroba, wycieczka szkolna itp.) i ewentualnie załącz dokument (np. zwolnienie).</li>
    <li><span class="manual-step-num">5</span>Trener dostanie powiadomienie. Status zmieni się na żółty.</li>
</ol>

<div class="manual-info">
    <strong>Po co usprawiedliwiać?</strong> Niektóre kluby uzależniają zniżki na składkach od „czystej frekwencji" — usprawiedliwione nieobecności nie psują statystyk. Poza tym trener wie, że dziecko ma realny powód, a nie po prostu olewa zajęcia.
</div>

<h2>Eksport do szkoły / na stypendium</h2>
<p>Pod listą jest przycisk <strong>Pobierz zaświadczenie o frekwencji</strong>. PDF zawiera podsumowanie obecności na zawodach i treningach — przyda się przy wnioskach o stypendia sportowe, zwolnienie z WF-u albo do szkoły mistrzostwa sportowego.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Dziecko twierdzi, że było, a trener wpisał nieobecność</summary>
    <p>Napisz do trenera bezpośrednio przez Wiadomości — wyjaśnicie. Trener może poprawić wpis, wszystkie zmiany są logowane w historii.</p>
</details>
<details>
    <summary>Czy klub może wykluczyć dziecko za niską frekwencję?</summary>
    <p>To zależy od regulaminu — niektóre kluby tak. Zwykle ostrzeżenie pojawia się przy frekwencji <50% przez 2 miesiące z rzędu. System wyśle Ci powiadomienie.</p>
</details>
<details>
    <summary>Mogę usprawiedliwić nieobecność wstecz?</summary>
    <p>Tak, do 7 dni. Po dłuższym czasie trzeba napisać do trenera/sekretariatu i poprosić o ręczną korektę.</p>
</details>
