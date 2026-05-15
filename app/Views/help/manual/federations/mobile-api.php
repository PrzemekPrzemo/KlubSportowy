<?php /** federations / mobile-api */ ?>
<p class="lead">Mobile API to interfejs dla aplikacji mobilnej klubu (own-brand) lub aplikacji partnerskich. Każdy token API ma określony scope (zakres uprawnień), TTL i może być w każdej chwili odwołany.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Wejdź w <strong>Ustawienia → API → Tokeny</strong>.</li>
    <li>Kliknij <em>+ Wygeneruj token</em>.</li>
    <li>Nazwij token (np. „Aplikacja mobilna Android v2").</li>
    <li>Wybierz scope: <em>read:members</em>, <em>read:schedule</em>, <em>write:attendance</em>, <em>read:finance</em>, <em>admin:*</em>.</li>
    <li>Określ TTL (do 1 roku) lub bezterminowo (z wymogiem rotacji co 90 dni).</li>
    <li>Skopiuj token — pokazujemy go raz, nie przechowujemy w formie odczytywalnej.</li>
</ol>

<div class="manual-callout manual-callout-danger">
    <strong><i class="bi bi-shield-exclamation"></i> Bezpieczeństwo.</strong> Token typu <em>admin:*</em> daje pełen dostęp do API klubu. Trzymaj go w sekretnym managerze (Vault, AWS Secrets, .env z chmod 600). Nigdy nie commituj do repozytorium.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Jakie limity?</summary>
        <div class="faq-body">Plan Starter: 1000 req/h. Pro: 10000 req/h. Enterprise: bez limitu (umowa SLA).</div>
    </details>
    <details>
        <summary>Jaki format API?</summary>
        <div class="faq-body">REST + JSON, OpenAPI 3.0 spec, JWT auth, dokumentacja na <code>api.clubdesk.pl/docs</code>.</div>
    </details>
    <details>
        <summary>Czy mogę unieważnić token?</summary>
        <div class="faq-body">Tak — natychmiast, w panelu. Każdy token można też wstrzymać czasowo (suspend).</div>
    </details>
</div>
