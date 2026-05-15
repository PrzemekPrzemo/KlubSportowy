<?php
$page = [
    'title'        => 'Status moich składek',
    'category'     => 'Zawodnik',
    'group'        => 'Płatności',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Status moich składek</h1>
<p class="lead">Tu zobaczysz wszystko, co dotyczy Twoich składek członkowskich: co zostało opłacone, co jest do zapłaty, jakie są terminy i czy w ogóle masz coś zaległego. Kolory działają jak światła drogowe — zielony = OK, żółty = uwaga, czerwony = pilne.</p>

<h2>Jak otworzyć ekran składek</h2>
<p>W menu portalu kliknij <strong>Składki</strong>. Zobaczysz listę wszystkich aktywnych zobowiązań na bieżący rok.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/fees</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-cash-stack text-primary"></i> Moje składki</h5>
            <div>
                <span class="badge bg-success">Opłacone: 480 zł</span>
                <span class="badge bg-warning text-dark ms-1">Do zapłaty: 120 zł</span>
            </div>
        </div>
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr><th>Okres</th><th>Tytuł</th><th>Kwota</th><th>Termin</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Styczeń 2026</td><td>Składka miesięczna</td><td>120 zł</td><td>10.01.2026</td>
                    <td><span class="badge bg-success">Opłacone</span></td>
                    <td><button class="btn btn-sm btn-link">Faktura PDF</button></td>
                </tr>
                <tr>
                    <td>Luty 2026</td><td>Składka miesięczna</td><td>120 zł</td><td>10.02.2026</td>
                    <td><span class="badge bg-success">Opłacone</span></td>
                    <td><button class="btn btn-sm btn-link">Faktura PDF</button></td>
                </tr>
                <tr>
                    <td>Marzec 2026</td><td>Składka miesięczna</td><td>120 zł</td><td>10.03.2026</td>
                    <td><span class="badge bg-success">Opłacone</span></td>
                    <td><button class="btn btn-sm btn-link">Faktura PDF</button></td>
                </tr>
                <tr>
                    <td>Kwiecień 2026</td><td>Składka miesięczna</td><td>120 zł</td><td>10.04.2026</td>
                    <td><span class="badge bg-success">Opłacone</span></td>
                    <td><button class="btn btn-sm btn-link">Faktura PDF</button></td>
                </tr>
                <tr class="table-warning">
                    <td>Maj 2026</td><td>Składka miesięczna</td><td>120 zł</td><td>10.05.2026</td>
                    <td><span class="badge bg-warning text-dark">Do zapłaty</span></td>
                    <td><button class="btn btn-sm btn-success">Zapłać online</button></td>
                </tr>
                <tr>
                    <td>Czerwiec 2026</td><td>Składka miesięczna</td><td>120 zł</td><td>10.06.2026</td>
                    <td><span class="badge bg-light text-dark border">Przyszła</span></td>
                    <td>—</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Lista składek z kolorowym statusem. Pomarańczowa pozycja jest najpilniejsza.</div>
</div>

<h2>Co oznaczają statusy</h2>
<ul>
    <li><span class="badge bg-success">Opłacone</span> — zapłacone, masz fakturę do pobrania.</li>
    <li><span class="badge bg-warning text-dark">Do zapłaty</span> — termin już mija lub minął, ale nie ma jeszcze poważnej zaległości.</li>
    <li><span class="badge bg-danger">Zaległe</span> — minął termin, klub może wstrzymać udział w treningach.</li>
    <li><span class="badge bg-light text-dark border">Przyszła</span> — wystawiona z wyprzedzeniem, jeszcze nie wymaga zapłaty.</li>
    <li><span class="badge bg-info">Zniżka</span> — masz rabat (rodzeństwo, frekwencja, sukcesy).</li>
    <li><span class="badge bg-secondary">Anulowane</span> — np. wycofane przez zarząd (np. choroba zwalniająca z opłat).</li>
</ul>

<h2>Subskrypcja / autopłatność</h2>
<p>Jeśli klub umożliwia płatność cykliczną, możesz włączyć <strong>autopłatność</strong> w sekcji <em>Subskrypcje</em>. Wtedy karta jest pobierana automatycznie każdego miesiąca i nigdy nie zapomnisz o przelewie. Możesz wyłączyć w każdej chwili.</p>

<div class="manual-tip">
    <strong>Zniżki.</strong> Niektóre kluby przyznają zniżki za frekwencję, dla rodzeństwa albo dla zawodników z sukcesami. Jeśli widzisz pozycję „Zniżka -20 zł" — to znaczy, że klub zaktualizował Ci taryfę automatycznie.
</div>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Zapłaciłem(am) przelewem, ale status nadal pokazuje „Do zapłaty"</summary>
    <p>Przelewy bankowe trafiają do księgowej po 1–2 dniach roboczych. Po zaksięgowaniu status automatycznie się zmieni. Płatność online (karta, BLIK) księguje się natychmiast.</p>
</details>
<details>
    <summary>Czy mogę zapłacić jednorazowo za cały rok?</summary>
    <p>Tak — w nagłówku ekranu jest przycisk „Zapłać wszystko" lub „Zapłać do końca sezonu". Niektóre kluby dają za to dodatkową zniżkę.</p>
</details>
<details>
    <summary>Nie zgadza się kwota — co robić?</summary>
    <p>Napisz do księgowości klubu (e-mail w stopce ekranu) lub do sekretariatu. Wszystkie zmiany są zapisywane, więc jeśli była pomyłka — łatwo ją cofnąć.</p>
</details>
