<?php /** Strona podręcznika: Wprowadzenie do ClubDesk */ ?>
<p class="lead">ClubDesk to kompleksowy system zarządzania klubem sportowym typu SaaS, łączący ewidencję członków, planowanie treningów, organizację turniejów, finanse, komunikację oraz integracje z federacjami w jednym panelu administracyjnym.</p>

<h2>Czym jest ClubDesk</h2>
<p>System został zaprojektowany w architekturze MVC w PHP i dostarczany jest jako usługa subskrypcyjna pod adresem <code>app.clubdesk.pl</code>. Każdy klub otrzymuje izolowane środowisko z własnym brandingiem, ustawieniami modułów oraz danymi członków. Jako administrator (rola <strong>zarząd</strong>) masz pełny dostęp do konfiguracji, raportów, finansów i danych osobowych, dlatego Twoje konto jest chronione mechanizmem MFA oraz audit logiem.</p>

<h2>Dla kogo jest ten podręcznik</h2>
<p>Dokumentacja kierowana jest do osób pełniących funkcję administratora klubu — prezesa, sekretarza, dyrektora zarządzającego lub osoby technicznej odpowiedzialnej za wdrożenie ClubDesk. Trenerzy, instruktorzy, sędziowie, lekarze oraz członkowie mają dedykowane przewodniki w sekcji <a href="<?= url('help') ?>">Pomoc</a>.</p>

<h2>Struktura podręcznika</h2>
<ol>
    <li><strong>Pierwsze kroki</strong> — logowanie, branding, plan subskrypcji, sekcje sportowe.</li>
    <li><strong>Członkowie</strong> — ewidencja, import, dokumenty, RODO.</li>
    <li><strong>Sport</strong> — treningi, obecność, turnieje, drabinki, ranking.</li>
    <li><strong>Finanse</strong> — składki, faktury, bramki płatności, prowizje, JPK.</li>
    <li><strong>Komunikacja</strong> — ogłoszenia, email, SMS.</li>
    <li><strong>Compliance</strong> — badania, certyfikacje, GDPR, WADA.</li>
    <li><strong>Federacje i integracje</strong> — PZ-y, Google Calendar, API mobilne.</li>
    <li><strong>Raporty i analityka</strong> — dashboard, KPI, audit log.</li>
</ol>

<h2>Jak nawigować</h2>
<p>Lewy panel zawiera spis treści całego podręcznika z grupowaniem po kategoriach. Pole wyszukiwania filtruje sekcje na żywo. Na dole każdej strony znajdziesz przyciski „Poprzednia / Następna", które pozwalają liniowo przejść przez cały materiał. Symbole wizualne w treści mają znaczenie:</p>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka.</strong> Niebieskie ramki zawierają tipy i dobre praktyki — warto je zaznaczyć.
</div>
<div class="manual-callout manual-callout-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga.</strong> Żółte ramki ostrzegają przed częstymi błędami, które mogą prowadzić do niespójności danych.
</div>
<div class="manual-callout manual-callout-danger">
    <strong><i class="bi bi-shield-exclamation"></i> Krytyczne.</strong> Czerwone ramki dotyczą operacji nieodwracalnych — np. anonimizacji członka czy usunięcia sekcji.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy mogę używać ClubDesk na telefonie?</summary>
        <div class="faq-body">Tak — panel jest w pełni responsywny, a dla członków dostępna jest PWA. Trenerzy i administratorzy mogą zaznaczać obecność, edytować dane i przeglądać raporty z urządzeń mobilnych.</div>
    </details>
    <details>
        <summary>Ilu administratorów może mieć jeden klub?</summary>
        <div class="faq-body">Nie ma sztywnego limitu — każdy plan subskrypcyjny pozwala dodać dowolną liczbę kont z rolą <em>zarząd</em>. Pamiętaj jednak, że każdy administrator ma dostęp do pełnego audit logu i danych finansowych, dlatego ograniczaj listę do osób faktycznie odpowiedzialnych za zarządzanie.</div>
    </details>
    <details>
        <summary>Czy moje dane są bezpieczne?</summary>
        <div class="faq-body">Dane przechowywane są w centrum danych na terenie EU, połączenie jest szyfrowane TLS 1.3, hasła hashowane bcryptem, a kopie zapasowe wykonywane są codziennie. Pełną politykę bezpieczeństwa znajdziesz w sekcji <a href="<?= url('legal/security') ?>">Bezpieczeństwo</a>.</div>
    </details>
</div>
