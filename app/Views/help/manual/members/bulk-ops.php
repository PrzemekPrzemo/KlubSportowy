<?php /** members / bulk-ops */ ?>
<p class="lead">Operacje grupowe pozwalają wykonać działanie na dziesiątkach lub setkach członków równocześnie — bez powtarzania kliknięć. Każda operacja masowa jest atomowa, logowana i odwracalna w ciągu 24h (z wyjątkiem wysyłki komunikacji).</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Na liście członków zaznacz checkboxy obok wybranych osób (lub <em>zaznacz wszystkie</em> aby zaznaczyć cały aktualny widok z filtrami).</li>
    <li>Na pasku akcji u góry pojawi się liczba zaznaczonych i lista operacji.</li>
    <li>Wybierz operację: <em>Wyślij e-mail</em>, <em>Wyślij SMS</em>, <em>Wystaw fakturę</em>, <em>Zmień status</em>, <em>Zmień sekcję</em>, <em>Eksport CSV/PDF</em>, <em>Zarchiwizuj</em>, <em>Anonimizuj</em>.</li>
    <li>Potwierdź akcję w modalu — system pokazuje liczbę osób i potencjalny koszt (np. dla SMS).</li>
    <li>Po wykonaniu otrzymasz powiadomienie z raportem (ile sukcesów, ile błędów).</li>
</ol>

<div class="manual-callout manual-callout-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Akcje nieodwracalne.</strong> Anonimizacja oraz wysyłka komunikacji są nieodwracalne — system wymaga dodatkowego potwierdzenia hasłem dla grup powyżej 50 osób.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy są limity?</summary>
        <div class="faq-body">Mass email — do 1000 odbiorców/h (plan Pro: 5000/h). Mass SMS — koszty zgodnie z cennikiem operatora. Eksporty bez limitów.</div>
    </details>
    <details>
        <summary>Czy mogę zaplanować operację na później?</summary>
        <div class="faq-body">Tak — dla maila i SMS dostępna jest opcja <em>Zaplanuj wysyłkę</em> z wyborem daty i godziny.</div>
    </details>
    <details>
        <summary>Jak cofnąć masową fakturę?</summary>
        <div class="faq-body">W ciągu 24h: <em>Finanse → Faktury → Operacje masowe → Cofnij</em>. Po tym czasie wymagane są noty korygujące.</div>
    </details>
</div>
