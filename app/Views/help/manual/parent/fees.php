<?php
$page = [
    'title'        => 'Płatność składek za dziecko',
    'category'     => 'Rodzic',
    'group'        => 'Składki',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Płatność składek za dziecko</h1>
<p class="lead">Najprostsza forma uregulowania składek — kartą, BLIK-iem, Apple Pay albo Google Pay, prosto z portalu opiekuna. Możesz zapłacić za jedno dziecko, za rodzeństwo naraz, albo ustawić autopłatność, żeby nigdy o tym nie pamiętać.</p>

<h2>Gdzie znajdę składki</h2>
<p>W portalu opiekuna, w karcie każdego dziecka, jest zakładka <strong>Składki</strong>. Pulpit również pokazuje skrót: ile masz do zapłaty w bieżącym miesiącu — łącznie dla wszystkich podopiecznych.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/guardian/fees</div>
    <div class="manual-mockup-content">
        <h5 class="mb-3"><i class="bi bi-credit-card-2-front text-primary"></i> Składki dla moich dzieci</h5>
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-circle"></i> Masz <strong>240 zł</strong> do zapłaty w sumie. Najpilniejszy termin: 10.05.2026.
        </div>
        <table class="table table-bordered align-middle mb-2">
            <thead class="table-light"><tr><th>Dziecko</th><th>Tytuł</th><th>Kwota</th><th>Termin</th><th>Status</th></tr></thead>
            <tbody>
                <tr><td>Anna</td><td>Składka maj — pływanie</td><td>120 zł</td><td>10.05.2026</td><td><span class="badge bg-success">Opłacone</span></td></tr>
                <tr class="table-warning"><td>Piotr</td><td>Składka maj — piłka ręczna</td><td>120 zł</td><td>10.05.2026</td><td><span class="badge bg-warning text-dark">Do zapłaty</span></td></tr>
                <tr class="table-warning"><td>Piotr</td><td>Składka kwiecień (zaległa)</td><td>120 zł</td><td>10.04.2026</td><td><span class="badge bg-danger">Zaległe</span></td></tr>
            </tbody>
        </table>
        <div class="d-flex gap-2 justify-content-end">
            <button class="btn btn-outline-primary">Zapłać zaznaczone</button>
            <button class="btn btn-success"><i class="bi bi-cash"></i> Zapłać wszystko (240 zł)</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Zbiorczy widok składek wszystkich Twoich dzieci.</div>
</div>

<h2>Płatność online — krok po kroku</h2>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź w <em>Składki</em>.</li>
    <li><span class="manual-step-num">2</span>Wybierz, co chcesz zapłacić — pojedynczą pozycję albo wszystko naraz.</li>
    <li><span class="manual-step-num">3</span>Kliknij <strong>Zapłać</strong>.</li>
    <li><span class="manual-step-num">4</span>Wybierz metodę: karta, BLIK, Apple/Google Pay.</li>
    <li><span class="manual-step-num">5</span>Potwierdź. Pieniądze trafiają natychmiast, faktura PDF jest gotowa po kilku sekundach.</li>
</ol>

<h2>Autopłatność (subskrypcja)</h2>
<p>Najwygodniejsze rozwiązanie dla rodzin z rosnącymi obowiązkami — raz konfigurujesz autopłatność i już nigdy nie musisz pamiętać o terminach:</p>
<ol>
    <li>Wejdź w <em>Składki → Subskrypcje</em>.</li>
    <li>Wybierz dziecko i zaznacz, którą składkę chcesz mieć automatyczną.</li>
    <li>Wpisz dane karty (jednorazowo, są zaszyfrowane).</li>
    <li>Co miesiąc kwota pobiera się automatycznie, fakturę dostajesz e-mailem.</li>
</ol>

<div class="manual-tip">
    <strong>Bezpiecznie i odwracalnie.</strong> Autopłatność możesz wyłączyć w każdej chwili — jedno kliknięcie. Twoja karta jest szyfrowana po stronie operatora (Stripe / Przelewy24), nigdy nie zapisuje się w klubie.
</div>

<h2>Faktury — na kogo wystawione</h2>
<p>Domyślnie faktury idą na <strong>Twoje dane jako opiekuna</strong> (imię, nazwisko, adres). Jeśli chcesz fakturę na firmę (np. żeby pracodawca refundował), wejdź w <em>Profil → Dane do faktury</em> i wpisz NIP. Od następnej wpłaty system użyje tych danych.</p>

<h2>Zniżki rodzeństwa</h2>
<p>Wiele klubów daje automatyczne zniżki rodzinom z więcej niż jednym dzieckiem. Jeśli widzisz w składce pozycję „Zniżka rodzeństwo -20 zł" — to znaczy, że system rozpoznał drugie dziecko i zaktualizował taryfę. Nie musisz nic zgłaszać.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Co jeśli nie zapłacę w terminie?</summary>
    <p>Najpierw status zmienia się na żółty (Do zapłaty). Po kolejnych dniach na czerwony (Zaległe). Klub może wstrzymać udział w treningach. Naliczanie odsetek zależy od regulaminu klubu.</p>
</details>
<details>
    <summary>Mogę zapłacić jednorazowo za cały sezon?</summary>
    <p>Tak — na liście jest przycisk „Zapłać do końca sezonu" albo „Zapłać cały rok". Niektóre kluby dają za to zniżkę 5–10%.</p>
</details>
<details>
    <summary>Karta odrzucona — co dalej?</summary>
    <p>Sprawdź limit operacji w aplikacji bankowej. Czasem trzeba potwierdzić 3D Secure SMS-em. Możesz też spróbować BLIK lub innej karty.</p>
</details>
