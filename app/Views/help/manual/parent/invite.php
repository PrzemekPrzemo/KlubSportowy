<?php
$page = [
    'title'        => 'Jak otrzymać dostęp do portalu',
    'category'     => 'Rodzic',
    'group'        => 'Wprowadzenie',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Jak otrzymać dostęp do portalu</h1>
<p class="lead">Dostęp do portalu opiekuna jest tworzony przez klub — nie zakładasz sam(a) konta z ulicy. Klub wysyła Ci specjalne zaproszenie na e-mail, a Ty w kilku krokach ustawiasz hasło i już jesteś w środku.</p>

<h2>Co musisz zrobić, żeby dostać zaproszenie</h2>
<p>To prosta sekwencja:</p>
<ol>
    <li>Zapisz swoje dziecko do klubu (papierowo lub przez formularz online).</li>
    <li>W formularzu zapisu podaj <strong>swój e-mail i telefon</strong> jako kontakt do opiekuna.</li>
    <li>Klub doda Cię do systemu jako opiekuna prawnego dziecka.</li>
    <li>System automatycznie wysyła Ci zaproszenie na podany e-mail.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>Twoja skrzynka e-mail</div>
    <div class="manual-mockup-content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong><i class="bi bi-envelope-paper text-primary"></i> Zaproszenie do portalu opiekuna — UKS Iskra</strong>
                    <small class="text-muted">noreply@clubdesk.pl</small>
                </div>
                <hr>
                <p class="small mb-2">Witaj Mario!</p>
                <p class="small mb-2">Klub <strong>UKS Iskra</strong> dodał Cię jako opiekuna prawnego dziecka <strong>Anna Kowalska</strong> (pływanie). Aby aktywować konto opiekuna i ustawić hasło, kliknij w przycisk poniżej:</p>
                <p class="text-center my-3">
                    <button class="btn btn-danger">Aktywuj konto opiekuna →</button>
                </p>
                <p class="small text-muted mb-0">Link aktywacyjny ważny przez 7 dni. Jeśli to pomyłka — zignoruj e-mail.</p>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Tak wygląda e-mail zaproszeniowy. Sprawdź folder SPAM, jeśli go nie widzisz.</div>
</div>

<h2>Pierwsze logowanie — krok po kroku</h2>
<ol>
    <li><span class="manual-step-num">1</span>Kliknij przycisk w e-mailu zaproszeniowym <strong>„Aktywuj konto"</strong>.</li>
    <li><span class="manual-step-num">2</span>System otworzy stronę ustawienia hasła. Wymyśl bezpieczne hasło: minimum 8 znaków, najlepiej z cyfrą i wielką literą.</li>
    <li><span class="manual-step-num">3</span>Potwierdź hasło i kliknij <strong>Zapisz</strong>.</li>
    <li><span class="manual-step-num">4</span>Trafisz prosto na pulpit opiekuna z listą Twoich dzieci.</li>
</ol>

<div class="manual-warn">
    <strong>Link ważny 7 dni.</strong> Jeśli przegapisz, użyj opcji „Nie pamiętasz hasła?" na stronie logowania i podaj swój e-mail — system wyśle nowy link.
</div>

<h2>Co jeśli nie dostałem(am) maila?</h2>
<ul>
    <li>Sprawdź folder <strong>SPAM</strong> w swojej poczcie.</li>
    <li>Sprawdź, czy klub na pewno dodał Cię do systemu (zapytaj sekretariat).</li>
    <li>Sprawdź, czy klub ma poprawny adres Twojego e-maila.</li>
    <li>Jeśli wszystko OK — poproś sekretariat o ponowne wysłanie zaproszenia.</li>
</ul>

<h2>Logowanie kolejnym razem</h2>
<p>Po aktywacji konta, każdy następny dostęp to:</p>
<ol>
    <li>Wejdź na adres podany przez klub — najczęściej <code>twoj-klub.clubdesk.pl/portal/login</code>.</li>
    <li>Wpisz e-mail i hasło opiekuna.</li>
    <li>Trafisz na pulpit.</li>
</ol>

<div class="manual-tip">
    <strong>Zabezpiecz konto.</strong> Konto opiekuna ma dostęp do danych dziecka i Twoich faktur — warto włączyć logowanie dwuetapowe (2FA) w sekcji <em>Profil → Bezpieczeństwo</em>.
</div>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Czy mogę używać tego samego konta co dziecko?</summary>
    <p>Nie. Konta są osobne. Konto rodzica obsługuje wszystkie sprawy prawne i finansowe, konto dziecka — sportowe (kalendarz, obecność).</p>
</details>
<details>
    <summary>Mam dwoje dzieci w klubie — dwa zaproszenia?</summary>
    <p>Nie. Otrzymasz jedno zaproszenie. Po aktywacji konta system automatycznie pokaże wszystkich Twoich podopiecznych.</p>
</details>
<details>
    <summary>Czy jest aplikacja mobilna?</summary>
    <p>Portal jest aplikacją PWA — możesz go zainstalować na telefonie z poziomu przeglądarki. Działa offline i wysyła powiadomienia. Instrukcja w manualu zawodnika (sekcja PWA).</p>
</details>
