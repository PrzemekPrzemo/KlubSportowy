<?php
$page = [
    'title'        => 'Historia płatności i faktury',
    'category'     => 'Zawodnik',
    'group'        => 'Płatności',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Historia płatności i faktury</h1>
<p class="lead">Każda Twoja wpłata zostaje zapisana w portalu na zawsze. Możesz wrócić do niej w każdej chwili — pobrać fakturę PDF, sprawdzić datę i kwotę albo wydrukować zaświadczenie. Przydaje się przy rozliczeniu rocznym albo gdy szkoła pyta o opłaty za sport.</p>

<h2>Gdzie znajdę historię</h2>
<p>W menu portalu kliknij <strong>Płatności</strong> → zakładka <strong>Historia</strong>. Zobaczysz pełną listę wszystkich transakcji posortowaną od najnowszej.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/payments</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-receipt text-primary"></i> Historia płatności</h5>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto">
                    <option>Rok 2026</option>
                    <option>Rok 2025</option>
                </select>
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i> Pobierz zestawienie</button>
            </div>
        </div>
        <table class="table table-sm align-middle">
            <thead><tr><th>Data</th><th>Tytuł</th><th>Metoda</th><th class="text-end">Kwota</th><th>Faktura</th></tr></thead>
            <tbody>
                <tr>
                    <td>09.05.2026</td>
                    <td>Składka maj 2026</td>
                    <td><i class="bi bi-credit-card"></i> Karta</td>
                    <td class="text-end fw-bold">120,00 zł</td>
                    <td><a class="btn btn-sm btn-link"><i class="bi bi-file-pdf"></i> FV-2026-0142</a></td>
                </tr>
                <tr>
                    <td>08.04.2026</td>
                    <td>Składka kwiecień 2026</td>
                    <td><i class="bi bi-phone"></i> BLIK</td>
                    <td class="text-end fw-bold">120,00 zł</td>
                    <td><a class="btn btn-sm btn-link"><i class="bi bi-file-pdf"></i> FV-2026-0118</a></td>
                </tr>
                <tr>
                    <td>10.03.2026</td>
                    <td>Składka marzec 2026</td>
                    <td><i class="bi bi-bank"></i> Przelew</td>
                    <td class="text-end fw-bold">120,00 zł</td>
                    <td><a class="btn btn-sm btn-link"><i class="bi bi-file-pdf"></i> FV-2026-0091</a></td>
                </tr>
                <tr>
                    <td>15.02.2026</td>
                    <td>Wpisowe (opłata startowa)</td>
                    <td><i class="bi bi-credit-card"></i> Karta</td>
                    <td class="text-end fw-bold">50,00 zł</td>
                    <td><a class="btn btn-sm btn-link"><i class="bi bi-file-pdf"></i> FV-2026-0074</a></td>
                </tr>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3">Łącznie w 2026</th>
                    <th class="text-end">410,00 zł</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="manual-mockup-caption">Pełna historia transakcji z linkami do faktur PDF.</div>
</div>

<h2>Pobranie faktury</h2>
<ol>
    <li>Znajdź transakcję na liście.</li>
    <li>Kliknij ikonkę <i class="bi bi-file-pdf"></i> w ostatniej kolumnie albo numer faktury (np. FV-2026-0142).</li>
    <li>Plik PDF otworzy się w przeglądarce lub pobierze automatycznie.</li>
</ol>

<h2>Faktura na firmę</h2>
<p>Jeśli chcesz, żeby klub wystawiał faktury na firmę (np. żeby pracodawca refundował składki), wejdź w <em>Profil → Dane do faktury</em> i wpisz NIP, nazwę firmy i adres. Od najbliższej wpłaty faktury będą generowane automatycznie z tymi danymi.</p>

<div class="manual-tip">
    <strong>Zestawienie roczne.</strong> Pod historią jest przycisk <strong>Pobierz zestawienie roczne</strong> — generuje jeden PDF z sumą wszystkich składek za rok. Przyda się przy uldze podatkowej (jeśli klub jest organizacją pożytku publicznego) albo przy rozliczeniu z pracodawcą.
</div>

<h2>Korekty i zwroty</h2>
<p>Jeśli klub zwrócił Ci nadpłatę albo wystawił korektę, zobaczysz to jako osobną pozycję ze znakiem minus i kwota będzie czerwona. Zawsze wyświetla się obok oryginalnej transakcji, więc łatwo skojarzysz.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Zapłaciłem(am), ale nie widzę faktury</summary>
    <p>Faktura jest generowana automatycznie w ciągu kilku minut po zaksięgowaniu. Jeśli płaciłeś(aś) przelewem bankowym — może minąć 1–2 dni roboczych, zanim księgowość potwierdzi wpłatę.</p>
</details>
<details>
    <summary>Czy klub trzyma moje faktury bezterminowo?</summary>
    <p>Tak. Faktury są archiwizowane zgodnie z prawem podatkowym — minimum 5 lat. Możesz je pobierać kiedykolwiek.</p>
</details>
<details>
    <summary>Potrzebuję zaświadczenia dla ZUS / pracodawcy</summary>
    <p>Pod historią jest przycisk <em>Pobierz zaświadczenie</em> — generuje oficjalny dokument z pieczęcią klubu. Możesz też napisać do księgowości, jeśli potrzebujesz szczególnego formatu.</p>
</details>
