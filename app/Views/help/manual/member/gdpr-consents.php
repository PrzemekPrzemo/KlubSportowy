<?php
$page = [
    'title'        => 'Moje zgody (RODO)',
    'category'     => 'Zawodnik',
    'group'        => 'Prywatność',
    'last_updated' => '2026-05-15',
    'reading_time' => '4 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Moje zgody (RODO)</h1>
<p class="lead">Klub przetwarza Twoje dane na różne sposoby — niektóre są obowiązkowe (np. ewidencja członka), ale wiele wymaga Twojej zgody. Na ekranie zgód RODO widzisz dokładnie, na co się zgodziłeś(aś), a czego nie, i możesz to w każdej chwili zmienić.</p>

<h2>Po co są zgody?</h2>
<p>RODO (rozporządzenie o ochronie danych osobowych) wymaga, żeby klub miał Twoją <strong>świadomą i odwoływalną zgodę</strong> na konkretne sposoby wykorzystania Twoich danych — na przykład publikację zdjęć z zawodów, wysyłanie SMS-ów marketingowych albo udostępnienie wyników federacji.</p>

<h2>Jak otworzyć ekran zgód</h2>
<p>W menu portalu kliknij <strong>Profil → Zgody</strong> albo bezpośrednio <em>/portal/consents</em>. Zobaczysz wszystkie aktywne i historyczne zgody.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/consents</div>
    <div class="manual-mockup-content">
        <h5 class="mb-3"><i class="bi bi-shield-check text-success"></i> Moje zgody RODO</h5>
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Publikacja zdjęć i wideo</strong>
                        <p class="text-muted small mb-0">Wyrażam zgodę na publikację zdjęć i nagrań z mojego udziału w treningach i zawodach na stronie klubu, w social media i w materiałach prasowych.</p>
                        <small class="text-muted">Aktualizowane: 12.09.2025</small>
                    </div>
                    <div class="form-check form-switch ms-3">
                        <input class="form-check-input" type="checkbox" checked style="transform: scale(1.5);">
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>SMS-y marketingowe</strong>
                        <p class="text-muted small mb-0">Zgoda na otrzymywanie SMS-ów o promocjach, eventach klubowych i ofertach partnerów.</p>
                        <small class="text-muted">Brak zgody</small>
                    </div>
                    <div class="form-check form-switch ms-3">
                        <input class="form-check-input" type="checkbox" style="transform: scale(1.5);">
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Udostępnianie danych federacji</strong>
                        <p class="text-muted small mb-0">Zgoda na przekazanie danych identyfikujących do związku sportowego (PESEL, data urodzenia) na potrzeby zgłoszeń do zawodów krajowych.</p>
                        <small class="text-muted">Aktualizowane: 03.10.2025</small>
                    </div>
                    <div class="form-check form-switch ms-3">
                        <input class="form-check-input" type="checkbox" checked style="transform: scale(1.5);">
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Publiczny profil zawodnika</strong>
                        <p class="text-muted small mb-0">Pozwala innym osobom (kibice, dziennikarze) zobaczyć moje imię, zdjęcie i osiągnięcia bez logowania.</p>
                    </div>
                    <div class="form-check form-switch ms-3">
                        <input class="form-check-input" type="checkbox" style="transform: scale(1.5);">
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-success mt-2"><i class="bi bi-save"></i> Zapisz zmiany</button>
    </div>
    <div class="manual-mockup-caption">Każda zgoda to osobny suwak — możesz włączyć/wyłączyć w każdej chwili.</div>
</div>

<h2>Jak zmienić zgodę</h2>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź na ekran Zgody.</li>
    <li><span class="manual-step-num">2</span>Kliknij suwak przy danej zgodzie — zmieni kolor z zielonego na szary (lub odwrotnie).</li>
    <li><span class="manual-step-num">3</span>Kliknij <strong>Zapisz zmiany</strong>.</li>
    <li><span class="manual-step-num">4</span>Dostaniesz e-mail z potwierdzeniem zmiany (dla bezpieczeństwa).</li>
</ol>

<div class="manual-info">
    <strong>Cofnięcie zgody nie działa wstecz.</strong> Jeśli wcześniej zgodziłeś(aś) się na publikację zdjęcia, a teraz to cofniesz — klub usunie zdjęcie z aktywnych kanałów (strona, social), ale nie z fizycznych albumów czy gazetek wydrukowanych w przeszłości. To zgodne z prawem.
</div>

<h2>Zgody obowiązkowe</h2>
<p>Niektóre zgody są wymagane, żeby w ogóle być członkiem klubu (np. zgoda na podstawowe przetwarzanie danych osobowych w celu prowadzenia ewidencji). Tych nie da się wyłączyć — mają plakietkę <span class="badge bg-secondary">Wymagana</span>. Jeśli się z nimi nie zgadzasz, jedyne rozwiązanie to rezygnacja z członkostwa i usunięcie konta.</p>

<h2>Historia zgód</h2>
<p>Pod listą jest sekcja <em>Historia zmian</em> — pokazuje wszystkie Twoje zmiany zgód w czasie. Klub ma obowiązek to logować, żeby udowodnić w razie kontroli, na co się zgodziłeś(aś) i kiedy.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Mogę cofnąć zgodę bez podawania powodu?</summary>
    <p>Tak — to Twoje prawo. Nie musisz tłumaczyć dlaczego.</p>
</details>
<details>
    <summary>Czy klub może nadal używać moich zdjęć z meczu, jeśli wycofam zgodę?</summary>
    <p>Klub usunie je z aktywnych miejsc (strona, social media) w rozsądnym czasie. Materiały historyczne (np. kronika klubowa, gazetki sprzed lat) mogą zostać.</p>
</details>
<details>
    <summary>Komu zgłosić nieprawidłowości?</summary>
    <p>Inspektor Ochrony Danych (IOD) klubu — kontakt znajdziesz w stopce ekranu i w klauzuli informacyjnej (link „Polityka prywatności" na dole strony).</p>
</details>
