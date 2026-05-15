<?php /** security / sessions */ ?>
<p class="lead">Sesje to aktywne logowania Twojego konta. Możesz zobaczyć listę urządzeń i przeglądarek, z których jesteś zalogowany, i jednym kliknięciem wylogować podejrzane sesje.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Wejdź w <strong>Mój profil → Bezpieczeństwo → Aktywne sesje</strong>.</li>
    <li>Lista pokazuje: urządzenie (Mac/Windows/iOS/Android), przeglądarka, lokalizacja (geoIP), data logowania, ostatnia aktywność.</li>
    <li>Twoja bieżąca sesja jest oznaczona zielonym znacznikiem.</li>
    <li>Aby wylogować konkretną sesję — kliknij <em>Wyloguj</em> przy wpisie.</li>
    <li>Aby wylogować wszystkie pozostałe — przycisk <em>Wyloguj wszędzie poza tym urządzeniem</em>.</li>
</ol>

<div class="manual-callout manual-callout-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Nietypowa lokalizacja.</strong> Jeśli widzisz sesję z nieznanej lokalizacji (np. innego kraju) — natychmiast ją wyloguj i zmień hasło. System też wyśle Ci alert mailowy o logowaniu z nowego IP.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Po jakim czasie sesja wygasa?</summary>
        <div class="faq-body">Domyślnie po 30 dniach bez aktywności. W ustawieniach klubu możesz skrócić (np. do 24h dla większego bezpieczeństwa).</div>
    </details>
    <details>
        <summary>Co przy zmianie hasła?</summary>
        <div class="faq-body">Wszystkie aktywne sesje (poza bieżącą) są automatycznie wylogowane.</div>
    </details>
    <details>
        <summary>Czy widzę sesje innych użytkowników?</summary>
        <div class="faq-body">Nie — tylko własne. Audit log zawiera natomiast wpisy o logowaniach wszystkich.</div>
    </details>
</div>
