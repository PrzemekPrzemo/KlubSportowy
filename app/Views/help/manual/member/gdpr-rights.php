<?php
$page = [
    'title'        => 'Eksport i usunięcie moich danych (RODO)',
    'category'     => 'Zawodnik',
    'group'        => 'Prywatność',
    'last_updated' => '2026-05-15',
    'reading_time' => '4 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Eksport i usunięcie moich danych</h1>
<p class="lead">RODO daje Ci dwa potężne prawa: możesz pobrać <strong>całą paczkę swoich danych</strong> z klubu (art. 20 — przenoszenie danych) albo <strong>zażądać ich usunięcia</strong> (art. 17 — prawo do bycia zapomnianym). Oba działania uruchomisz z jednego ekranu — bez maili, telefonów i papierków.</p>

<h2>Gdzie znaleźć ekran RODO</h2>
<p>W menu portalu kliknij <strong>Profil → Moje dane (RODO)</strong> albo wpisz w przeglądarce <em>/portal/gdpr</em>. To centralny pulpit Twoich praw — zawsze pod ręką.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/gdpr</div>
    <div class="manual-mockup-content">
        <h5 class="mb-3"><i class="bi bi-shield-fill-check text-primary"></i> Moje dane osobowe (RODO)</h5>
        <p class="text-muted small">Tutaj możesz wyeksportować swoje dane albo wnioskować o ich usunięcie. To Twoje prawo gwarantowane przez Rozporządzenie o Ochronie Danych Osobowych (RODO).</p>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-primary h-100">
                    <div class="card-body">
                        <h6><i class="bi bi-file-earmark-zip text-primary"></i> Eksport danych (art. 20)</h6>
                        <p class="small text-muted">Pobierz pełną paczkę z wszystkimi swoimi danymi w formacie ZIP: profil, składki, obecności, wyniki, wiadomości, zgody.</p>
                        <button class="btn btn-primary btn-sm"><i class="bi bi-download"></i> Wnioskuj o eksport</button>
                        <hr>
                        <small class="text-muted d-block">Ostatni eksport: 12.03.2026 (gotowy)</small>
                        <a class="btn btn-outline-primary btn-sm mt-1"><i class="bi bi-cloud-download"></i> Pobierz paczkę (4.2 MB)</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-danger h-100">
                    <div class="card-body">
                        <h6><i class="bi bi-trash text-danger"></i> Usunięcie konta (art. 17)</h6>
                        <p class="small text-muted">Wnioskuj o usunięcie wszystkich swoich danych z klubu (anonimizacja). Operacja nieodwracalna.</p>
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-shield-exclamation"></i> Rozpocznij proces</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 alert alert-light border small">
            <strong>Inne prawa:</strong> prawo dostępu, prawo do sprostowania danych, prawo do ograniczenia przetwarzania.
            Skontaktuj się z <a href="mailto:iod@klub.pl">iod@klub.pl</a> (Inspektor Ochrony Danych klubu).
        </div>
    </div>
    <div class="manual-mockup-caption">Pulpit RODO — dwie główne akcje: eksport i usunięcie.</div>
</div>

<h2>Eksport danych — krok po kroku</h2>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź w <em>Profil → Moje dane (RODO)</em>.</li>
    <li><span class="manual-step-num">2</span>Kliknij <strong>Wnioskuj o eksport</strong>.</li>
    <li><span class="manual-step-num">3</span>Potwierdź swój e-mail (link weryfikacyjny — to dla Twojego bezpieczeństwa).</li>
    <li><span class="manual-step-num">4</span>System zaczyna zbierać dane. Czas: zwykle 5–30 minut, zależnie od ilości.</li>
    <li><span class="manual-step-num">5</span>Gdy paczka jest gotowa — dostaniesz e-mail z linkiem do pobrania. Link działa 7 dni.</li>
</ol>

<h3>Co znajdziesz w paczce</h3>
<ul>
    <li>Plik <code>profile.json</code> — Twoje dane osobowe.</li>
    <li>Folder <code>fees/</code> — historia składek i faktury PDF.</li>
    <li>Folder <code>attendance/</code> — historia obecności.</li>
    <li>Folder <code>results/</code> — wyniki turniejów.</li>
    <li>Folder <code>messages/</code> — Twoja korespondencja (eksport JSON).</li>
    <li>Plik <code>consents.json</code> — historia zgód RODO.</li>
    <li>Plik <code>README.txt</code> — wyjaśnienie, co jest w paczce.</li>
</ul>

<h2>Usunięcie konta (prawo do bycia zapomnianym)</h2>
<p>To znacznie poważniejsza operacja — po jej wykonaniu klub anonimizuje (lub usuwa) wszystkie Twoje dane. Konto przestaje działać, nie zalogujesz się ponownie, członkostwo wygasa.</p>

<div class="manual-warn">
    <strong>Co zostaje po usunięciu?</strong> Niektóre dane klub musi prawnie zachować nawet po Twoim odejściu — np. faktury (5 lat przepisów podatkowych) i wyniki zawodów (zwykle nazwisko zastąpione kodem typu „Zawodnik_142"). System wyjaśni to przed potwierdzeniem.
</div>

<h3>Krok po kroku</h3>
<ol>
    <li>Wejdź w <em>RODO → Usunięcie konta</em>.</li>
    <li>Przeczytaj informację o tym, co zostanie usunięte, a co zachowane.</li>
    <li>Wpisz powód (opcjonalnie — pomaga klubowi się poprawić).</li>
    <li>Wpisz swoje hasło i kliknij <strong>Potwierdzam</strong>.</li>
    <li>Klub ma do 30 dni na realizację wniosku. Dostaniesz e-mail z potwierdzeniem zakończenia.</li>
</ol>

<div class="manual-info">
    <strong>Co jeśli zmieniam zdanie?</strong> Masz 7 dni „okres oczekiwania" — w tym czasie możesz anulować wniosek. Po 7 dniach operacja jest nieodwracalna.
</div>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Czy mogę poprosić tylko o część danych?</summary>
    <p>Tak. W formularzu eksportu możesz odznaczyć kategorie, które nie interesują Cię. Np. tylko składki bez wiadomości.</p>
</details>
<details>
    <summary>Co jeśli mam zaległe składki, a chcę usunąć konto?</summary>
    <p>Klub może uzależnić usunięcie od uregulowania zobowiązań finansowych — to zgodne z prawem (uzasadniony interes prawny). Najpierw rozliczasz, potem usuwasz.</p>
</details>
<details>
    <summary>Komu zgłosić, jeśli klub nie reaguje?</summary>
    <p>Możesz złożyć skargę do Prezesa Urzędu Ochrony Danych Osobowych (UODO) — to organ nadzorczy w Polsce. Najpierw jednak skontaktuj się z IOD klubu (kontakt na ekranie).</p>
</details>
<details>
    <summary>Czy klub może odmówić eksportu danych?</summary>
    <p>Nie. Eksport jest Twoim prawem podstawowym z RODO. Klub musi go zrealizować bezpłatnie w ciągu 30 dni.</p>
</details>
