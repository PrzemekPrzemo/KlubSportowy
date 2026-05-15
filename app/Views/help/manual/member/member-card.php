<?php
$page = [
    'title'        => 'Karta członkowska (wirtualna)',
    'category'     => 'Zawodnik',
    'group'        => 'Mój profil',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Karta członkowska</h1>
<p class="lead">Twoja wirtualna legitymacja klubowa — zawsze pod ręką w telefonie. Pokażesz ją na zawodach, zbiórce, w obiekcie sportowym albo gdy klub robi kontrolę składek. Z kodem QR i Twoim zdjęciem.</p>

<h2>Jak otworzyć kartę</h2>
<ol>
    <li>Zaloguj się do portalu (lub otwórz aplikację).</li>
    <li>W górnej belce kliknij <strong>Karta członkowska</strong> (ikonka legitymacji).</li>
    <li>Karta wyświetli się na pełnym ekranie — można obrócić telefon poziomo.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/member-card</div>
    <div class="manual-mockup-content">
        <div class="mx-auto" style="max-width:360px;">
            <div class="card text-white" style="background: linear-gradient(135deg,#232232 0%,#EE2C28 100%); border:none;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <small class="text-white-50 text-uppercase">UKS Iskra</small>
                            <h6 class="mb-0">Karta zawodnika</h6>
                        </div>
                        <span class="badge bg-light text-dark">2026</span>
                    </div>
                    <div class="d-flex gap-3 align-items-center">
                        <div style="width:80px;height:80px;border-radius:50%;background:#fff3; border:2px solid #fff; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-person" style="font-size:2.5rem;"></i>
                        </div>
                        <div>
                            <h5 class="mb-0">Anna Kowalska</h5>
                            <small>Pływanie · Senior</small>
                            <div class="small text-white-50 mt-1">Nr: 2026-0142</div>
                        </div>
                    </div>
                    <hr class="border-light">
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <small class="text-white-50">Status</small>
                            <div><span class="badge bg-success">Aktywna</span></div>
                            <small class="text-white-50 d-block mt-1">Ważna do: 31.12.2026</small>
                        </div>
                        <div style="width:72px;height:72px;background:#fff;padding:5px;border-radius:6px;">
                            <div style="background:repeating-linear-gradient(45deg,#000 0 4px,#fff 4px 8px); width:100%; height:100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Karta członkowska — kolory pochodzą z brandingu Twojego klubu.</div>
</div>

<h2>Do czego się przydaje</h2>
<ul>
    <li><strong>Wejście na zajęcia</strong> — zeskanujesz QR przy wejściu na halę lub basen.</li>
    <li><strong>Weryfikacja na zawodach</strong> — sędzia widzi, że masz aktywne członkostwo.</li>
    <li><strong>Zniżki partnerskie</strong> — jeśli klub ma umowy z partnerami (sklepy, restauracje).</li>
    <li><strong>Dowód opłaconej składki</strong> — gdy zarząd zechce sprawdzić.</li>
</ul>

<h2>Status karty — kolory</h2>
<ul>
    <li><span class="badge bg-success">Aktywna</span> — wszystko opłacone, możesz korzystać.</li>
    <li><span class="badge bg-warning text-dark">Wymaga uwagi</span> — np. wygasają badania lekarskie lub składka.</li>
    <li><span class="badge bg-danger">Nieaktywna</span> — zaległa składka lub wygasłe dokumenty.</li>
</ul>

<div class="manual-info">
    <strong>Pobierz PDF.</strong> Pod kartą jest przycisk „Pobierz PDF" — można wydrukować i nosić w portfelu jako zapasową kopię.
</div>

<h2>Dodanie do Apple Wallet / Google Wallet</h2>
<p>Na ekranie karty (na telefonie) zobaczysz przycisk <strong>„Dodaj do portfela"</strong>. Po jednym kliknięciu karta trafia do natywnego portfela telefonu — możesz ją wtedy pokazać nawet bez otwierania aplikacji.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Karta pokazuje „Nieaktywna" — co robić?</summary>
    <p>Sprawdź sekcję Składki — najczęściej powód to nieopłacona rata. Po zaksięgowaniu wpłaty (online: natychmiast) karta automatycznie wraca do statusu Aktywna.</p>
</details>
<details>
    <summary>Czy mogę używać karty bez internetu?</summary>
    <p>Tak. Jeśli zainstalujesz portal jako aplikację (PWA), karta jest dostępna offline z ostatniego synchronizowanego stanu.</p>
</details>
<details>
    <summary>Czy każdy klub ma karty?</summary>
    <p>Tak — funkcja jest standardowa w ClubDesku. Wygląd (kolory, logo) zależy od brandingu Twojego klubu.</p>
</details>
