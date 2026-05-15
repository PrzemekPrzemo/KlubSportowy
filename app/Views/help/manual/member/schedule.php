<?php
$page = [
    'title'        => 'Mój kalendarz',
    'category'     => 'Zawodnik',
    'group'        => 'Treningi',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Mój kalendarz</h1>
<p class="lead">Kalendarz to Twój główny widok wszystkich wydarzeń klubowych. Treningi, mecze, turnieje, eventy klubowe — wszystko ułożone na osi czasu. Możesz przełączać widoki (dzień / tydzień / miesiąc) i klikać w każde wydarzenie, żeby zobaczyć szczegóły.</p>

<h2>Jak otworzyć kalendarz</h2>
<p>W górnej belce portalu kliknij <strong>Plan zajęć</strong> (ikonka kalendarza). Domyślnie zobaczysz widok tygodnia z najbliższymi treningami.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/schedule</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">18–24 maja 2026</h5>
                <small class="text-muted">Twój tydzień treningowy</small>
            </div>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-secondary">Dzień</button>
                <button class="btn btn-sm btn-primary">Tydzień</button>
                <button class="btn btn-sm btn-outline-secondary">Miesiąc</button>
            </div>
        </div>
        <table class="table table-bordered mb-0">
            <thead>
                <tr class="text-center small text-muted">
                    <th>Pon 18</th><th>Wt 19</th><th>Śr 20</th><th>Czw 21</th><th>Pt 22</th><th>Sb 23</th><th>Nd 24</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><div class="p-1 small bg-primary text-white rounded">17:00<br>Trening</div></td>
                    <td></td>
                    <td><div class="p-1 small bg-primary text-white rounded">17:00<br>Trening</div></td>
                    <td></td>
                    <td><div class="p-1 small bg-primary text-white rounded">17:00<br>Trening</div></td>
                    <td><div class="p-1 small bg-warning text-dark rounded">10:00<br>Mecz</div></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Widok tygodnia z treningami (niebieskie) i meczem (żółty).</div>
</div>

<h2>Co oznaczają kolory</h2>
<ul>
    <li><span class="badge bg-primary">Niebieski</span> — regularny trening.</li>
    <li><span class="badge bg-warning text-dark">Żółty</span> — mecz, sparing, turniej.</li>
    <li><span class="badge bg-success">Zielony</span> — Twoja obecność potwierdzona przez trenera.</li>
    <li><span class="badge bg-danger">Czerwony</span> — nieobecność (usprawiedliwiona lub nie).</li>
    <li><span class="badge bg-secondary">Szary</span> — odwołany trening.</li>
</ul>

<h2>Co zobaczysz klikając w wydarzenie</h2>
<p>Po kliknięciu w „kafelek" treningu zobaczysz pełne szczegóły: trenera, miejsce (z mapą), zaplanowane ćwiczenia, listę uczestników (jeśli klub na to pozwala) i opcje:</p>
<ul>
    <li><strong>Zapisz się</strong> / <strong>Wypisz się</strong> — jeśli trening wymaga zapisów (opis na osobnej stronie).</li>
    <li><strong>Dodaj do kalendarza telefonu</strong> — eksport do Google / Apple Calendar.</li>
    <li><strong>Otwórz mapę</strong> — nawigacja Google Maps prosto do obiektu.</li>
    <li><strong>Skontaktuj się z trenerem</strong> — szybka wiadomość, jeśli nie możesz przyjść.</li>
</ul>

<div class="manual-tip">
    <strong>Subskrypcja kalendarza.</strong> Możesz „podpiąć" kalendarz klubowy do swojego Google / Apple Calendar przez specjalny link iCal — wtedy treningi pojawiają się automatycznie w Twoim telefonie. Link znajdziesz pod kalendarzem w portalu.
</div>

<h2>Filtrowanie</h2>
<p>Jeśli klub ma kilka sekcji, w które jesteś zaangażowany(a) (np. pływanie + siatkówka), nad kalendarzem są zakładki — przełączasz się między dyscyplinami albo wybierasz <em>Wszystkie</em>.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Nie widzę dzisiejszego treningu — co się stało?</summary>
    <p>Trening mógł zostać odwołany (zobacz Ogłoszenia) albo zmieniony na inną godzinę. Sprawdź też filtry — może masz zaznaczoną inną sekcję.</p>
</details>
<details>
    <summary>Czy mogę zobaczyć swój kalendarz na komputerze?</summary>
    <p>Tak. Wszystko działa tak samo na komputerze, telefonie i tablecie. Widok automatycznie dopasowuje się do ekranu.</p>
</details>
<details>
    <summary>Czy mogę widzieć treningi kolegów?</summary>
    <p>Jeśli klub na to pozwala, lista uczestników jest widoczna po wejściu w trening. Możesz wtedy zobaczyć kto będzie.</p>
</details>
