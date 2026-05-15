<?php /** reports / audit-log */ ?>
<p class="lead">Audit log to chronologiczny zapis wszystkich operacji administracyjnych w klubie — kto, kiedy, co i z jakiego IP. Niezbędny dla zgodności z RODO (art. 30) i bezpieczeństwa wewnętrznego.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Wejdź w <strong>Ustawienia → Bezpieczeństwo → Audit log</strong>.</li>
    <li>Filtruj po: typie akcji (login, member.created, invoice.sent, gdpr.export, etc.), użytkowniku, dacie, IP.</li>
    <li>Każdy wpis pokazuje: timestamp, użytkownik, akcja, target object, IP, user agent, wartości przed/po (dla edycji).</li>
    <li>Eksport do CSV/JSON dla audytora zewnętrznego lub Inspektora Ochrony Danych.</li>
    <li>Retencja: 5 lat (zgodnie z ustawą o rachunkowości i RODO).</li>
</ol>

<div class="manual-callout manual-callout-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Niemodyfikowalność.</strong> Audit log jest tylko do odczytu — nawet administrator klubu nie może go edytować ani usunąć. To wymóg zgodności i ochrony przed nadużyciami wewnętrznymi.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy widzę logowania nieudane?</summary>
        <div class="faq-body">Tak — wpisy <code>auth.login.failed</code> z IP i adresem mailowym próbującym się zalogować.</div>
    </details>
    <details>
        <summary>Co z logami serwera (Apache/Nginx)?</summary>
        <div class="faq-body">Osobny system — dostępne tylko dla supportu ClubDesk na żądanie.</div>
    </details>
    <details>
        <summary>Czy mogę dostać alert o podejrzanej aktywności?</summary>
        <div class="faq-body">Tak — w <em>Bezpieczeństwo → Alerty</em>: nietypowe IP, logowania nocne, masowe akcje, etc.</div>
    </details>
</div>
