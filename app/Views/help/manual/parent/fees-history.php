<?php
$page = [
    'title'        => 'Historia i faktury',
    'category'     => 'Rodzic',
    'group'        => 'Składki',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Historia i faktury</h1>
<p class="lead">Każda Twoja wpłata za dziecko jest zapisana w portalu na zawsze. Pobierzesz fakturę PDF, zaświadczenie roczne (do ulg podatkowych lub refundacji pracodawcy), albo zestawienie zbiorcze za rodzinę. Wszystko bez kontaktu z księgową.</p>

<h2>Gdzie znajdę historię</h2>
<p>W menu portalu opiekuna kliknij <strong>Płatności → Historia</strong>. Zobaczysz transakcje za wszystkich Twoich podopiecznych w jednym miejscu, posortowane od najnowszej.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/guardian/payments</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-receipt text-primary"></i> Historia płatności (rodzina)</h5>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto">
                    <option>Wszystkie dzieci</option>
                    <option>Anna</option>
                    <option>Piotr</option>
                </select>
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Pobierz zestawienie</button>
            </div>
        </div>
        <table class="table table-sm align-middle">
            <thead><tr><th>Data</th><th>Za kogo</th><th>Tytuł</th><th class="text-end">Kwota</th><th>Faktura</th></tr></thead>
            <tbody>
                <tr><td>09.05.2026</td><td>Anna</td><td>Składka maj — pływanie</td><td class="text-end">120,00 zł</td><td><a class="btn btn-sm btn-link"><i class="bi bi-file-pdf"></i> FV-2026-0142</a></td></tr>
                <tr><td>09.05.2026</td><td>Piotr</td><td>Składka maj — piłka ręczna</td><td class="text-end">120,00 zł</td><td><a class="btn btn-sm btn-link"><i class="bi bi-file-pdf"></i> FV-2026-0143</a></td></tr>
                <tr><td>08.04.2026</td><td>Anna</td><td>Składka kwiecień</td><td class="text-end">120,00 zł</td><td><a class="btn btn-sm btn-link"><i class="bi bi-file-pdf"></i> FV-2026-0118</a></td></tr>
                <tr><td>08.04.2026</td><td>Piotr</td><td>Składka kwiecień</td><td class="text-end">120,00 zł</td><td><a class="btn btn-sm btn-link"><i class="bi bi-file-pdf"></i> FV-2026-0119</a></td></tr>
                <tr><td>15.03.2026</td><td>Anna</td><td>Wpisowe na zawody wojewódzkie</td><td class="text-end">80,00 zł</td><td><a class="btn btn-sm btn-link"><i class="bi bi-file-pdf"></i> FV-2026-0089</a></td></tr>
            </tbody>
            <tfoot class="table-light">
                <tr><th colspan="3">Łącznie w 2026 (rodzina)</th><th class="text-end">560,00 zł</th><th></th></tr>
            </tfoot>
        </table>
    </div>
    <div class="manual-mockup-caption">Zbiorcza historia wpłat za wszystkie dzieci w jednym miejscu.</div>
</div>

<h2>Pobranie faktury</h2>
<p>Kliknij ikonkę <i class="bi bi-file-pdf"></i> lub numer faktury (np. FV-2026-0142) w ostatniej kolumnie. PDF otworzy się w przeglądarce albo pobierze do <em>Pobrane</em>.</p>

<h2>Faktura na firmę</h2>
<p>Jeśli chcesz, żeby faktura była wystawiona na firmę (np. dla refundacji pracodawcy), wejdź w <em>Profil → Dane do faktury</em> i wpisz NIP, nazwę firmy i adres. Działa od następnej wpłaty.</p>

<h2>Zestawienie roczne i ulgi podatkowe</h2>
<p>Pod listą jest przycisk <strong>Pobierz zestawienie roczne</strong>. PDF zawiera:</p>
<ul>
    <li>Sumę wszystkich składek za rok kalendarzowy.</li>
    <li>Rozbicie na dzieci.</li>
    <li>NIP klubu (do uznania w zeznaniu podatkowym).</li>
    <li>Pieczęć i podpis (cyfrowy).</li>
</ul>

<div class="manual-tip">
    <strong>Ulga podatkowa.</strong> Jeśli Twój klub jest organizacją pożytku publicznego (OPP) — sprawdź regulamin — opłaty mogą być uznawane jako darowizna w zeznaniu PIT. Klub zaznaczy to wyraźnie w zaświadczeniu.
</div>

<h2>Filtrowanie</h2>
<p>Nad listą jest filtr: <em>Wszystkie dzieci / pojedyncze dziecko</em>. Możesz też zawęzić zakres dat. Pomocne, gdy szukasz konkretnej faktury sprzed kilku miesięcy.</p>

<h2>Korekty i zwroty</h2>
<p>Jeśli kiedyś klub anulował składkę albo zwrócił nadpłatę, zobaczysz to jako osobną pozycję ze znakiem minus i kwota na czerwono. Pojawi się obok oryginalnej transakcji — łatwo skojarzysz.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Zapłaciłem(am), ale brak faktury</summary>
    <p>Faktura jest generowana w ciągu kilku minut po zaksięgowaniu wpłaty. Płatność online (karta, BLIK) jest błyskawiczna. Przelew bankowy: 1–2 dni robocze.</p>
</details>
<details>
    <summary>Potrzebuję faktury sprzed 2 lat — gdzie szukać?</summary>
    <p>Przewiń listę albo użyj filtra rok. Klub ma obowiązek przechowywać faktury minimum 5 lat (zgodnie z prawem podatkowym).</p>
</details>
<details>
    <summary>Czy mogę dostać duplikat papierowy?</summary>
    <p>Napisz do księgowości klubu — wydrukują i wyślą pocztą za niewielką opłatą za korespondencję. Wersja PDF z portalu ma jednak tę samą moc prawną.</p>
</details>
