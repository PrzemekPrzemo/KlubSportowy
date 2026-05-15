<?php /** finance / recurring */ ?>
<p class="lead">Recurring payments (płatności cykliczne) pozwalają zawodnikom raz autoryzować kartę, a potem system automatycznie pobiera składki co miesiąc. Brak ręcznego ścigania i 95%+ ściągalność.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Włącz <em>Płatności cykliczne</em> w <strong>Finanse → Bramki → Stripe → Funkcje</strong> (wymaga Stripe).</li>
    <li>Zawodnik (lub jego opiekun) loguje się do portalu i w sekcji <em>Płatności → Autopłatność</em> wprowadza dane karty.</li>
    <li>System tworzy subskrypcję Stripe powiązaną z planem składek tego zawodnika.</li>
    <li>W dniu naliczenia (np. 1. każdego miesiąca) system automatycznie obciąża kartę.</li>
    <li>Zawodnik otrzymuje fakturę mailem i może w każdej chwili anulować autopłatność.</li>
</ol>

<div class="manual-callout manual-callout-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Failed payments.</strong> Jeśli karta zostanie odrzucona (np. wygasła), system automatycznie ponawia 3 razy w odstępach 3-7 dni i informuje zawodnika oraz administratora.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy zawodnik może zmienić kartę?</summary>
        <div class="faq-body">Tak — w dowolnym momencie w portalu. Stara karta jest deaktywowana, nowa przejmuje aktywne subskrypcje.</div>
    </details>
    <details>
        <summary>Co przy zmianie wysokości składki?</summary>
        <div class="faq-body">Następna płatność zostanie pobrana w nowej wysokości. Jeśli kwota wzrosła >25%, członek dostaje powiadomienie z wymaganą akceptacją.</div>
    </details>
    <details>
        <summary>Czy płatności cykliczne wymagają zgody PSD2/SCA?</summary>
        <div class="faq-body">Tak — Stripe obsługuje pełną zgodność z dyrektywą PSD2 (Strong Customer Authentication).</div>
    </details>
</div>
