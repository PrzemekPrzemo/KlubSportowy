<?php
$page = [
    'title'        => 'Ogłoszenia i powiadomienia',
    'category'     => 'Zawodnik',
    'group'        => 'Komunikacja',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Ogłoszenia i powiadomienia</h1>
<p class="lead">Klub komunikuje się z Tobą przede wszystkim przez ogłoszenia — to taka klubowa „tablica informacyjna" w portalu. Każdy ważny komunikat (zmiana godziny treningu, zbiórka, turniej, opłata startowa) trafia tutaj i równolegle przychodzi do Ciebie powiadomieniem.</p>

<h2>Gdzie szukać ogłoszeń</h2>
<p>W menu portalu kliknij <strong>Ogłoszenia</strong>. Lista jest posortowana od najnowszych, a te nieprzeczytane mają wytłuszczony tytuł i czerwoną kropkę.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/announcements</div>
    <div class="manual-mockup-content">
        <h5 class="mb-3"><i class="bi bi-megaphone text-primary"></i> Ogłoszenia klubowe</h5>
        <div class="list-group">
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1 fw-bold">Trening w piątek odwołany <span class="badge bg-danger">Nowe</span></h6>
                    <small class="text-muted">22.05 · 12:30</small>
                </div>
                <p class="mb-1 small">Z powodu awarii pompy basen jest nieczynny. Następny trening w poniedziałek o 17:00.</p>
                <small class="text-muted">Trener Jan Nowak · do: Sekcja Pływanie</small>
            </div>
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">Zbiórka na turniej regionalny — Łódź 12.06</h6>
                    <small class="text-muted">19.05 · 18:00</small>
                </div>
                <p class="mb-1 small">Zbiórka 12.06 o 7:30 pod halą. Bus klubowy, powrót około 20:00. Zgłoszenia do 30.05.</p>
                <small class="text-muted">Zarząd · do: Wszyscy zawodnicy</small>
            </div>
            <div class="list-group-item">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">Pomiar postępów — termin majowy</h6>
                    <small class="text-muted">15.05 · 10:00</small>
                </div>
                <p class="mb-1 small">W tym tygodniu zbieramy pomiary czasów na 50m i 100m. Pamiętajcie o nawodnieniu.</p>
                <small class="text-muted">Trener Anna Wiśniewska</small>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Lista ogłoszeń z tagami pilności i grupami docelowymi.</div>
</div>

<h2>Powiadomienia — gdzie one przychodzą</h2>
<p>Ogłoszenie nie tylko trafia na listę — system od razu próbuje Cię o nim powiadomić. Masz cztery kanały do wyboru:</p>
<ul>
    <li><strong>Push w aplikacji</strong> (jeśli zainstalowałeś PWA i zezwoliłeś na powiadomienia).</li>
    <li><strong>E-mail</strong> — na adres z profilu.</li>
    <li><strong>SMS</strong> — tylko ważne komunikaty, jeśli klub ma usługę SMS.</li>
    <li><strong>Czerwona kropka</strong> w portalu — gdy się zalogujesz.</li>
</ul>

<h2>Konfiguracja powiadomień</h2>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź w <em>Profil → Preferencje powiadomień</em>.</li>
    <li><span class="manual-step-num">2</span>Dla każdego rodzaju komunikatu (ogłoszenia, zmiany treningu, składki, wiadomości) ustaw kanały.</li>
    <li><span class="manual-step-num">3</span>Możesz też włączyć tryb <em>„nie przeszkadzać"</em> — np. wyłącz powiadomienia po 22:00.</li>
    <li><span class="manual-step-num">4</span>Kliknij Zapisz.</li>
</ol>

<div class="manual-info">
    <strong>Pilne ogłoszenia.</strong> Niektóre komunikaty (np. odwołanie treningu, ewakuacja) mają flagę „Pilne". Te zawsze idą przez wszystkie aktywne kanały, niezależnie od Twoich preferencji — to dla Twojego bezpieczeństwa.
</div>

<h2>Czytanie ogłoszeń</h2>
<p>Kliknij w tytuł — otworzysz pełną treść. Po przeczytaniu, komunikat automatycznie traci status „Nowe". Niektóre ogłoszenia (np. regulaminy) wymagają potwierdzenia — wtedy zobaczysz przycisk <strong>„Potwierdzam, że przeczytałem(am)"</strong>.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Nie dostaję powiadomień push</summary>
    <p>Sprawdź: 1) czy zainstalowałeś portal jako aplikację (PWA), 2) czy zezwoliłeś na powiadomienia w przeglądarce/telefonie, 3) czy w profilu masz włączony kanał push.</p>
</details>
<details>
    <summary>Czy mogę odpowiedzieć na ogłoszenie?</summary>
    <p>Ogłoszenia są zwykle „do odczytu", ale jeśli klub na to pozwoli — zobaczysz pole komentarza pod treścią. Możesz też napisać wiadomość prywatną do nadawcy.</p>
</details>
<details>
    <summary>Czy ogłoszenia można zarchiwizować?</summary>
    <p>Tak — w prawym górnym rogu każdego ogłoszenia jest ikona „Archiwizuj". Nie usuwa się go z systemu, tylko znika z głównej listy.</p>
</details>
