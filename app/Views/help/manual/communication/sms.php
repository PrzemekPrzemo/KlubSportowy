<?php /** communication / sms */ ?>
<p class="lead">Kampanie SMS są skuteczne tam, gdzie e-mail może się nie przebić — pilne ogłoszenia (odwołany trening), przypomnienia o płatnościach, kody weryfikacyjne. ClubDesk integruje się z dostawcami SMS API obsługującymi Polskę.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Skonfiguruj dostawcę w <strong>Komunikacja → SMS → Konfiguracja</strong> — wspieramy SMSAPI.pl, Twilio, Vonage, gatewayAPI.</li>
    <li>Doładuj konto u dostawcy (typowo 0,06-0,12 zł za SMS w PL).</li>
    <li>Stwórz kampanię: treść SMS (max 160 znaków na 1 segment), nadawca (nazwa klubu, do 11 znaków).</li>
    <li>Wybierz odbiorców — tylko ci z potwierdzonym numerem telefonu i zgodą na SMS.</li>
    <li>Wyślij od razu lub zaplanuj.</li>
</ol>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> Templaty.</strong> Stwórz szablony dla najczęstszych komunikatów (odwołany trening, przypomnienie składki, gratulacje po meczu) — wysyłanie zajmuje wtedy 10 sekund.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Ile kosztuje SMS?</summary>
        <div class="faq-body">Cena zależy od dostawcy. SMSAPI: 0,06-0,10 zł, Twilio: 0,09-0,15 zł. ClubDesk nie pobiera prowizji od SMS.</div>
    </details>
    <details>
        <summary>Co z polskimi znakami?</summary>
        <div class="faq-body">Tak, obsługujemy. SMS z polskimi znakami to 70 znaków na segment (zamiast 160) — system ostrzega.</div>
    </details>
    <details>
        <summary>Czy mogę wysłać 2-way SMS?</summary>
        <div class="faq-body">Tak — z odpowiedziami trafiającymi do panelu. Wymaga numeru zwrotnego u dostawcy.</div>
    </details>
</div>
