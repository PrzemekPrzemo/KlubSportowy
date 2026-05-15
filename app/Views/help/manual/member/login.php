<?php
$page = [
    'title'        => 'Logowanie i hasło',
    'category'     => 'Zawodnik',
    'group'        => 'Pierwsze kroki',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Logowanie i hasło</h1>
<p class="lead">Żeby wejść do portalu zawodnika, potrzebujesz tylko dwóch rzeczy: adresu e-mail (lub loginu nadanego przez klub) i hasła. Jeżeli ktoś z klubu dodał Cię do systemu — dostałeś(aś) e-mail z linkiem aktywacyjnym.</p>

<h2>Krok po kroku</h2>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź na adres podany przez klub — najczęściej to <code>twoj-klub.clubdesk.pl/portal/login</code> (klub może mieć też własną domenę).</li>
    <li><span class="manual-step-num">2</span>Wpisz swój <strong>e-mail</strong> (ten, który podałeś(aś) zapisując się do klubu).</li>
    <li><span class="manual-step-num">3</span>Wpisz <strong>hasło</strong>. Jeśli to pierwsze logowanie, hasło ustawiłeś(aś) klikając w link z maila powitalnego.</li>
    <li><span class="manual-step-num">4</span>Kliknij <strong>Zaloguj się</strong>. Trafisz prosto na pulpit.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/login</div>
    <div class="manual-mockup-content">
        <div class="mx-auto" style="max-width: 380px;">
            <div class="text-center mb-3">
                <i class="bi bi-person-circle" style="font-size:3rem; color:#EE2C28;"></i>
                <h5 class="mt-2">Portal zawodnika</h5>
                <small class="text-muted">UKS Iskra</small>
            </div>
            <label class="form-label small">E-mail</label>
            <input class="form-control mb-2" value="anna.kowalska@example.com">
            <label class="form-label small">Hasło</label>
            <input type="password" class="form-control mb-2" value="••••••••">
            <div class="d-flex justify-content-between small mb-3">
                <label><input type="checkbox" checked> Zapamiętaj mnie</label>
                <a href="#">Nie pamiętasz hasła?</a>
            </div>
            <button class="btn btn-danger w-100">Zaloguj się</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Ekran logowania — wygląda dokładnie tak na każdym urządzeniu.</div>
</div>

<h2>Nie pamiętasz hasła?</h2>
<p>Spokojnie, każdemu się zdarza:</p>
<ol>
    <li>Pod polem hasła kliknij <strong>„Nie pamiętasz hasła?"</strong>.</li>
    <li>Wpisz swój e-mail i kliknij <em>Wyślij link</em>.</li>
    <li>Sprawdź skrzynkę pocztową — w ciągu kilku minut dostaniesz e-mail z linkiem (jeśli nie widzisz, zajrzyj do SPAMu).</li>
    <li>Kliknij link, ustaw nowe hasło (minimum 8 znaków, najlepiej z liczbą i wielką literą) i zaloguj się.</li>
</ol>

<div class="manual-warn">
    <strong>Uwaga.</strong> Link do resetu jest ważny tylko przez 1 godzinę i tylko raz. Jeśli przegapisz — wygeneruj nowy.
</div>

<h2>Weryfikacja dwuetapowa (2FA)</h2>
<p>Jeśli chcesz dodatkowo zabezpieczyć konto, w <em>Profil → Bezpieczeństwo</em> możesz włączyć logowanie dwuetapowe. Wtedy po haśle podajesz jeszcze 6-cyfrowy kod z aplikacji typu Google Authenticator. Polecamy, jeśli logujesz się z różnych urządzeń.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Nie dostałem(am) maila powitalnego od klubu</summary>
    <p>Sprawdź folder SPAM. Jeśli nadal nic — napisz na sekretariat klubu albo użyj opcji „Nie pamiętasz hasła?" wpisując e-mail, który podałeś(aś) klubowi.</p>
</details>
<details>
    <summary>Mogę zmienić swój e-mail do logowania?</summary>
    <p>Tak. Po zalogowaniu wejdź w <em>Profil</em> i zmień adres e-mail. Stary login przestanie działać natychmiast po potwierdzeniu nowego adresu.</p>
</details>
<details>
    <summary>Czy mogę się logować odciskiem palca / Face ID?</summary>
    <p>Tak — jeśli zainstalujesz portal jako aplikację (PWA), telefon zaproponuje Ci szybkie logowanie biometryczne.</p>
</details>
