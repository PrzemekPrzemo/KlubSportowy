<?php
$page = [
    'title'        => 'Zapisy na trening i wypisanie',
    'category'     => 'Zawodnik',
    'group'        => 'Treningi',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Zapisy na trening</h1>
<p class="lead">Niektóre kluby wymagają, żebyś zapisywał(a) się na konkretne zajęcia z wyprzedzeniem — np. na siłownię z ograniczoną liczbą miejsc albo na trening na lodowisku. Zapisy działają błyskawicznie: jedno kliknięcie i jesteś na liście.</p>

<h2>Jak rozpoznać trening, który wymaga zapisów</h2>
<p>Takie wydarzenie w kalendarzu ma dodatkową ikonkę <i class="bi bi-pencil-square"></i> i tekst „Wymaga zapisu". Po kliknięciu zobaczysz licznik miejsc (np. „12 z 16 zajętych") i przycisk <strong>Zapisz mnie</strong>.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/schedule (szczegóły treningu)</div>
    <div class="manual-mockup-content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-primary mb-1">Trening</span>
                        <h5 class="mb-0">Pływanie · technika</h5>
                        <small class="text-muted"><i class="bi bi-geo-alt"></i> Basen Miejski · Trener: Jan Nowak</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">Pon. 18 maja</div>
                        <div class="text-muted small">17:00 – 18:30</div>
                    </div>
                </div>
                <hr>
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">Zajętość</small>
                        <div class="progress" style="height:18px;">
                            <div class="progress-bar bg-success" style="width:75%;">12 / 16</div>
                        </div>
                        <small class="text-success">Jeszcze 4 miejsca</small>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <button class="btn btn-success"><i class="bi bi-check-circle"></i> Zapisz mnie</button>
                        <button class="btn btn-outline-secondary"><i class="bi bi-bell"></i> Powiadom mnie</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Trening z zapisami — zielony pasek mówi, ile miejsc zostało.</div>
</div>

<h2>Zapisanie się — krok po kroku</h2>
<ol>
    <li><span class="manual-step-num">1</span>Otwórz trening w kalendarzu.</li>
    <li><span class="manual-step-num">2</span>Kliknij zielony przycisk <strong>Zapisz mnie</strong>.</li>
    <li><span class="manual-step-num">3</span>Gotowe — Twoje miejsce jest zarezerwowane. Zobaczysz potwierdzenie na ekranie.</li>
    <li><span class="manual-step-num">4</span>Dostaniesz przypomnienie e-mailem / pushem na 24h i 1h przed treningiem.</li>
</ol>

<h2>Co jeśli wszystkie miejsca są zajęte?</h2>
<p>Wtedy przycisk zmienia się na <strong>„Zapisz się na listę rezerwową"</strong>. Gdy ktoś się wypisze, system automatycznie wciągnie pierwszą osobę z listy rezerwowej i wyśle Ci powiadomienie. Możesz spokojnie zapomnieć — system o tym pamięta.</p>

<h2>Wypisanie się z treningu</h2>
<ol>
    <li>Wejdź ponownie w trening, na który jesteś zapisany(a).</li>
    <li>Kliknij <strong>„Wypisz mnie"</strong>.</li>
    <li>Podaj krótki powód (opcjonalnie — pomaga trenerowi).</li>
    <li>Twoje miejsce zwolni się i trafi do osoby z listy rezerwowej.</li>
</ol>

<div class="manual-warn">
    <strong>Uwaga na deadline.</strong> Klub może ustawić limit wypisania — np. „nie później niż 2h przed treningiem". Późniejsze wypisanie jest możliwe, ale może liczyć się jako nieobecność.
</div>

<h2>Zapis się cyklicznie</h2>
<p>Jeśli zawsze chodzisz na poniedziałkowe pływanie — kliknij <strong>„Zapisz mnie na cały cykl"</strong>. System zarezerwuje Ci miejsce na każdym treningu w tej serii. Możesz później wypisać się z konkretnego dnia, jak Ci coś wypadnie.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Nie widzę przycisku „Zapisz mnie"</summary>
    <p>Albo trening nie wymaga zapisów (po prostu przychodzisz), albo zapisy są już zamknięte (np. trener wyłączył je rano przed treningiem). Sprawdź czas zamknięcia w opisie wydarzenia.</p>
</details>
<details>
    <summary>Czy mogę zapisać kolegę?</summary>
    <p>Nie. Każdy zawodnik zapisuje się sam ze swojego konta. To zabezpieczenie, żeby ktoś nie zajął miejsca nieświadomie.</p>
</details>
<details>
    <summary>Co jak nie przyjdę bez wypisania?</summary>
    <p>Zostaniesz oznaczony(a) jako nieobecność nieusprawiedliwiona. Niektóre kluby blokują wtedy zapisy na kolejne treningi do końca tygodnia.</p>
</details>
