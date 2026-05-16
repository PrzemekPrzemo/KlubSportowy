<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Korekty faktur</h1>
<p class="lead">
    Korekta faktury to oficjalny dokument, który zmienia zapis poprzedniej
    faktury — w polskim systemie księgowym nigdy nie "edytujemy" faktury, tylko
    wystawiamy korektę. ClubDesk pilnuje tego rygorystycznie.
</p>

<h2>Kiedy wystawiać korektę</h2>
<ul>
    <li><strong>Pomyłka w kwocie</strong> — np. wpisano 280 zł zamiast 320 zł.</li>
    <li><strong>Pomyłka w danych nabywcy</strong> — błędny adres, nazwisko, NIP.</li>
    <li><strong>Anulowanie usługi</strong> — zawodnik wypisał się w trakcie miesiąca,
        klub zwraca część składki.</li>
    <li><strong>Zwrot</strong> — z dowolnego powodu, gdy klub musi zwrócić środki.</li>
    <li><strong>Zmiana wysokości ulgi</strong> wstecz — rzadkie, ale się zdarza
        (sprawa stypendium decyduje się w trakcie miesiąca).</li>
</ul>

<h2>Proces korekty krok po kroku</h2>
<ol>
    <li>Wchodzisz na fakturę, którą chcesz skorygować.</li>
    <li>Klikasz <strong>"Wystaw korektę"</strong>.</li>
    <li>Wybierasz typ korekty: <em>kwoty</em>, <em>danych</em> lub <em>obu</em>.</li>
    <li>Wpisujesz nowe wartości — system pokazuje różnicę (np. "-40 zł").</li>
    <li>Wpisujesz powód korekty (obowiązkowe pole).</li>
    <li>Zatwierdzasz. System generuje fakturę korygującą z osobnym numerem.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/invoices/FV-2026-05-047/correction</div>
    <div class="manual-mockup-content">
        <h6>Korekta faktury FV-2026/05/047</h6>
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr><th>Pole</th><th>Przed korektą</th><th>Po korekcie</th></tr>
            </thead>
            <tbody>
                <tr><td>Nabywca</td><td>Anna Wójcik</td><td>Anna Wójcik</td></tr>
                <tr><td>Pozycja</td><td>Składka maj 2026 — Skrzaty U-9</td><td>Składka maj 2026 — Skrzaty U-9</td></tr>
                <tr class="table-warning"><td>Kwota netto</td><td>280,00 zł</td><td><strong>140,00 zł</strong></td></tr>
                <tr class="table-warning"><td>Powód</td><td>—</td><td><em>Stypendium socjalne -50% przyznane 15.05.2026, korekta wsteczna</em></td></tr>
            </tbody>
        </table>
        <div class="alert alert-warning small">
            <strong>Różnica:</strong> −140 zł (zwrot do klienta lub zaliczenie na poczet kolejnej faktury).
        </div>
        <div class="d-flex gap-2 justify-content-end">
            <button class="btn btn-outline-secondary">Anuluj</button>
            <button class="btn btn-primary">Wystaw korektę FV-K-2026/05/047</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: formularz wystawiania korekty z porównaniem "przed/po".</div>
</div>

<h2>Numeracja korekt</h2>
<p>
    Faktura korygująca ma osobny prefiks (typowo "FV-K") i własną numerację
    ciągłą. Korekta linkuje do oryginalnej faktury — zawsze wiadomo, której
    dotyczy. Nie można wystawić korekty do korekty (drugi raz korygowana
    faktura wymaga skomplikowanego procesu — kierujesz wtedy do księgowości).
</p>

<h2>Korekta "na minus" — zwrot pieniędzy</h2>
<p>
    Gdy korekta zmniejsza kwotę (np. anulujemy usługę), powstaje <em>nadpłata</em>
    klienta. ClubDesk zaproponuje dwa scenariusze:
</p>
<ul>
    <li><strong>Zaliczyć na poczet kolejnej faktury</strong> — domyślne, najprostsze.</li>
    <li><strong>Zwrócić na konto klienta</strong> — wymaga akcji księgowości.</li>
</ul>

<h2>Korekta "na plus" — dopłata</h2>
<p>
    Gdy korekta zwiększa kwotę (np. zapomnieliśmy doliczyć opłaty turniejowej),
    klient dostaje korektę z poleceniem zapłaty na różnicę. System automatycznie
    aktualizuje status (jeśli oryginalna była opłacona, korekta na plus pojawia
    się jako "oczekująca na płatność").
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Korekta jest dokumentem księgowym — po wystawieniu nie można jej anulować
    ani edytować. Wymaga akceptacji księgowego klubu, zanim trafi do klienta.
    Sekretariat tworzy korektę, ale wysyłka wymaga "potwierdzenia drugiej osoby"
    (zasada dwóch par oczu w finansach).
</div>

<h2>Wysyłka korekty</h2>
<p>
    Po zatwierdzeniu korekty klient dostaje:
</p>
<ul>
    <li>E-mail z PDF korekty.</li>
    <li>Wpis w jego karcie finansowej (z linkiem do oryginału).</li>
    <li>Informacja o różnicy (do zwrotu / do dopłaty).</li>
</ul>

<h2>Audyt korekt</h2>
<p>
    Korekty są raportowane miesięcznie do księgowości i widoczne w
    <em>Finanse → Raport korekt</em>. Wysoki współczynnik korekt (powyżej 5%
    wystawionych faktur) jest sygnałem, że proces generowania ma defekt — np.
    słownik ulg jest nieaktualny.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Przed wystawieniem korekty zawsze upewnij się, że problemu nie da się
    rozwiązać <em>w obrębie kolejnej faktury</em> (np. dodać tam pozycję
    "korekta z ub. miesiąca"). Dla małych kwot to często prostsze, szczególnie
    gdy klient jeszcze nie zapłacił oryginału.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
