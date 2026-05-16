<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Status płatności — kto zapłacił, kto nie</h1>
<p class="lead">
    Po wygenerowaniu faktur druga kluczowa operacja to <em>monitorowanie płatności</em>.
    ClubDesk automatycznie kojarzy przelewy z fakturami (jeśli klub ma
    integrację bankową), a sekretariat zajmuje się tylko obsługą wyjątków —
    przelewami "z błędnym tytułem", gotówką wpłaconą w kasie, zaległościami.
</p>

<h2>Lista faktur z filtrem statusu</h2>
<p>
    W <strong>Finanse → Faktury</strong> domyślny widok pokazuje wszystkie
    faktury miesiąca. Filtr "Status" pozwala szybko wyświetlić:
</p>
<ul>
    <li><strong>Wystawione</strong> — wszystkie aktywne (niezapłacone i zapłacone).</li>
    <li><strong>Opłacone</strong> — zapłata wpłynęła i jest skojarzona.</li>
    <li><strong>Oczekujące</strong> — wystawione, ale termin jeszcze nie minął.</li>
    <li><strong>Zaległe</strong> — termin minął, brak płatności.</li>
    <li><strong>Częściowo opłacone</strong> — wpłynęło mniej niż całość.</li>
    <li><strong>Anulowane</strong> — wycofane przed wysyłką.</li>
</ul>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/invoices?status=overdue</div>
    <div class="manual-mockup-content">
        <h6>Faktury zaległe (18 osób · 4 850 zł)</h6>
        <div class="d-flex justify-content-between mb-2">
            <div>
                <button class="btn btn-sm btn-outline-warning"><i class="bi bi-envelope"></i> Wyślij masowe przypomnienie</button>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-telephone"></i> Lista do telefonu</button>
            </div>
            <small class="text-muted">Sortuj: <em>zaległość rosnąco</em></small>
        </div>
        <table class="table table-sm table-striped">
            <thead class="table-light">
                <tr><th>Faktura</th><th>Członek</th><th>Wystawiona</th><th>Termin</th><th class="text-end">Kwota</th><th>Zaległość</th><th>Akcja</th></tr>
            </thead>
            <tbody>
                <tr><td>FV-2026/04/092</td><td>Cezary Nowak</td><td>01.04</td><td>14.04</td><td class="text-end">280 zł</td><td><span class="text-danger">29 dni</span></td><td><button class="btn btn-sm btn-outline-primary">Szczegóły</button></td></tr>
                <tr><td>FV-2026/04/138</td><td>Tomasz Lewandowski</td><td>01.04</td><td>14.04</td><td class="text-end">320 zł</td><td><span class="text-danger">29 dni</span></td><td><button class="btn btn-sm btn-outline-primary">Szczegóły</button></td></tr>
                <tr><td>FV-2026/04/201</td><td>Magdalena Kowalska</td><td>01.04</td><td>14.04</td><td class="text-end">256 zł</td><td><span class="text-danger">29 dni</span></td><td><button class="btn btn-sm btn-outline-primary">Szczegóły</button></td></tr>
                <tr><td>FV-2026/05/047</td><td>Anna Wójcik</td><td>01.05</td><td>14.05</td><td class="text-end">280 zł</td><td><span class="text-warning">0 dni</span></td><td><button class="btn btn-sm btn-outline-primary">Szczegóły</button></td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Mockup: lista zaległych faktur z akcjami "wyślij przypomnienie" / "lista do telefonu".</div>
</div>

<h2>Automatyczne kojarzenie przelewów</h2>
<p>
    Jeżeli klub zintegrował konto bankowe (najczęściej przez API banku lub
    bramkę typu Tpay/Stripe), przelewy są kojarzone automatycznie:
</p>
<ul>
    <li>Po tytule przelewu (gdy zawiera numer faktury).</li>
    <li>Po kwocie i nazwisku (gdy tytuł jest "byle jaki").</li>
    <li>Po referencji bramki płatniczej (gdy klient płaci on-line z linku w mailu).</li>
</ul>
<p>
    Skuteczność auto-kojarzenia w typowym klubie to 92–95%. Pozostałe 5–8%
    wymaga ręcznej akcji sekretariatu.
</p>

<h2>Ręczne zaznaczenie płatności</h2>
<p>
    Gdy ktoś zapłacił gotówką w sekretariacie albo zrobił przelew bez tytułu —
    znajdujesz fakturę, klikasz <strong>Zaznacz jako opłacone</strong> i
    wpisujesz datę i metodę (gotówka / przelew / karta). Operacja jest logowana
    z Twoim podpisem.
</p>

<h2>Płatność częściowa</h2>
<p>
    Jeżeli wpłynęło mniej niż cała faktura (np. 200 z 280 zł), klikasz
    <em>"Zaksięguj częściową wpłatę"</em>, wpisujesz 200 zł, system pokazuje
    fakturę jako "Częściowo opłacona — pozostało 80 zł". Faktura nadal pojawi
    się w przypomnieniach, ale z odpowiednią kwotą.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Nadpłaty (klient zapłacił więcej) tworzą <strong>saldo dodatnie</strong> na
    koncie członka. ClubDesk automatycznie zaliczy nadpłatę na poczet kolejnej
    faktury. Jeżeli klient prosi o zwrot — kierujesz do księgowości
    (sekretariat nie wykonuje zwrotów).
</div>

<h2>Karta finansowa członka</h2>
<p>
    Z poziomu profilu członka (zakładka "Finanse") widzisz pełną historię
    transakcji: faktury, wpłaty, korekty, saldo. To przydatne narzędzie podczas
    rozmów telefonicznych — w 5 sekund wiesz, na czym stoi.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Filtr "Zaległe &gt;30 dni" zwykle warto wykonywać raz w tygodniu (poniedziałek)
    i przeglądać listę dzwoniąco-przypominająco. Klubom utrzymującym ten rytm
    udaje się trzymać należności poniżej 2% miesięcznych wpływów.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
