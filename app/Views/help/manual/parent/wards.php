<?php
$page = [
    'title'        => 'Lista moich podopiecznych',
    'category'     => 'Rodzic',
    'group'        => 'Moje dziecko',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Lista moich podopiecznych</h1>
<p class="lead">Jeśli masz w klubie więcej niż jedno dziecko (rodzeństwo, w tej samej lub różnych sekcjach), portal pozwala zarządzać nimi z jednego konta. Lista podopiecznych to Twój punkt startowy — widzisz status każdego dziecka i jednym kliknięciem przechodzisz do jego szczegółów.</p>

<h2>Jak otworzyć listę</h2>
<p>Lista pojawia się od razu po zalogowaniu — to jest pulpit opiekuna. W każdej chwili możesz do niej wrócić, klikając logo klubu w lewym górnym rogu albo zakładkę <strong>Moi podopieczni</strong>.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/guardian</div>
    <div class="manual-mockup-content">
        <h5 class="mb-3"><i class="bi bi-people-fill text-primary"></i> Moi podopieczni</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-success h-100">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center mb-3">
                            <div style="width:64px;height:64px;border-radius:50%;background:#dee2e6;display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-person fs-2"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Anna Kowalska</h6>
                                <small class="text-muted">16 lat · Pływanie · Senior</small>
                            </div>
                        </div>
                        <div class="d-flex gap-1 mb-2">
                            <span class="badge bg-success">Aktywna</span>
                            <span class="badge bg-light text-dark border">Składka: opłacona</span>
                        </div>
                        <div class="small text-muted">
                            <div>Frekwencja: <strong class="text-success">88%</strong> w maju</div>
                            <div>Następny trening: pon. 17:00</div>
                        </div>
                        <button class="btn btn-sm btn-outline-primary mt-2 w-100">Otwórz profil <i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-warning h-100">
                    <div class="card-body">
                        <div class="d-flex gap-3 align-items-center mb-3">
                            <div style="width:64px;height:64px;border-radius:50%;background:#dee2e6;display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-person fs-2"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Piotr Kowalski</h6>
                                <small class="text-muted">12 lat · Piłka ręczna · U13</small>
                            </div>
                        </div>
                        <div class="d-flex gap-1 mb-2">
                            <span class="badge bg-success">Aktywna</span>
                            <span class="badge bg-warning text-dark">Składka 120 zł</span>
                        </div>
                        <div class="small text-muted">
                            <div>Frekwencja: <strong class="text-warning">72%</strong> w maju</div>
                            <div>Następny trening: wt. 16:30</div>
                        </div>
                        <button class="btn btn-sm btn-warning mt-2 w-100">Zapłać składkę <i class="bi bi-credit-card"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 alert alert-info small mb-0">
            <i class="bi bi-info-circle"></i> Masz ważne sprawy do załatwienia dla 1 dziecka.
        </div>
    </div>
    <div class="manual-mockup-caption">Pulpit z kafelkami dla każdego dziecka — od razu widać, gdzie wymagana jest reakcja.</div>
</div>

<h2>Co zobaczysz na każdym kafelku</h2>
<ul>
    <li><strong>Zdjęcie i imię</strong> dziecka.</li>
    <li><strong>Sekcja sportowa</strong> i kategoria wiekowa.</li>
    <li><strong>Status członkostwa</strong> — kolorowa plakietka (zielona = OK, żółta = uwaga).</li>
    <li><strong>Status składek</strong> — opłacone / do zapłaty / zaległe.</li>
    <li><strong>Frekwencja w bieżącym miesiącu</strong> — szybki wgląd.</li>
    <li><strong>Najbliższy trening</strong> — żebyś wiedział(a), kiedy i dokąd zawieźć dziecko.</li>
</ul>

<h2>Kolejność dzieci</h2>
<p>Domyślnie podopieczni są ustawieni alfabetycznie. Jeśli chcesz inną kolejność (np. najstarszy najpierw), kliknij ikonkę <i class="bi bi-arrows-move"></i> w prawym górnym rogu listy — przeciągnij kafelki w preferowaną kolejność.</p>

<h2>Co jeśli dziecko nie pojawia się na liście?</h2>
<ul>
    <li>Sprawdź, czy klub na pewno powiązał Was w systemie (czasem zapominają o tym po zapisaniu nowego dziecka).</li>
    <li>Zaloguj się ponownie — czasami trzeba odświeżyć sesję.</li>
    <li>Napisz do sekretariatu klubu z prośbą o sprawdzenie powiązania.</li>
</ul>

<div class="manual-tip">
    <strong>Dziecko, które już nie trenuje.</strong> Były podopieczny (np. który skończył 18 lat lub zrezygnował) znika z listy automatycznie. Faktury i historia pozostają jednak dostępne w archiwum.
</div>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Ile dzieci może być na jednym koncie?</summary>
    <p>Nie ma limitu. Niektórzy rodzice mają nawet 4–5 dzieci w jednym klubie — system to bez problemu obsługuje.</p>
</details>
<details>
    <summary>Co jeśli rodzic adopcyjny / dziadek opiekuje się dzieckiem?</summary>
    <p>Każda osoba prawnie odpowiedzialna za niepełnoletniego może mieć konto opiekuna. Klub wymaga tylko dokumentu potwierdzającego (akt urodzenia, postanowienie sądu).</p>
</details>
<details>
    <summary>Czy moje dzieci widzą siebie nawzajem?</summary>
    <p>Konta dzieci są osobne. Z konta dziecka nie ma podglądu na rodzeństwo — to chroni prywatność każdego.</p>
</details>
