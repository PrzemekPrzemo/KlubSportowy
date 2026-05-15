<?php /** finance / jpk */ ?>
<p class="lead">JPK_FA (Jednolity Plik Kontrolny — Faktury) to obowiązkowy format raportowania VAT do Urzędu Skarbowego. ClubDesk generuje pliki XML zgodne ze schemą JPK_FA(3) z 2022 r., gotowe do importu do programów księgowych lub bezpośredniej wysyłki.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Wejdź w <strong>Finanse → Eksport → JPK_FA</strong>.</li>
    <li>Wybierz okres rozliczeniowy (zwykle miesiąc).</li>
    <li>Sprawdź podsumowanie: liczba faktur, suma netto, VAT, brutto.</li>
    <li>Kliknij <strong>Wygeneruj XML</strong> — plik zostanie utworzony zgodnie ze schemą Ministerstwa Finansów.</li>
    <li>Pobierz plik i przekaż księgowej lub zaimportuj do swojego programu księgowego.</li>
</ol>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> Pełna paczka.</strong> Eksport zawiera też JPK_V7M (jeśli klub jest VAT-owcem) oraz PDF z podsumowaniem dla księgowej.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy mój klub musi rozliczać VAT?</summary>
        <div class="faq-body">Tak, jeśli przekracza próg 200 000 zł rocznego obrotu lub świadczy usługi opodatkowane VAT. Konsultacja z księgową jest niezbędna.</div>
    </details>
    <details>
        <summary>Co ze sprawozdaniami GUS?</summary>
        <div class="faq-body">Sprawozdania F-01 i SOF generujemy w sekcji <em>Finanse → Raporty GUS</em>.</div>
    </details>
    <details>
        <summary>Jak często wysyłać JPK?</summary>
        <div class="faq-body">JPK_V7M co miesiąc do 25. dnia następnego miesiąca. JPK_FA wyłącznie na żądanie US (gdy poprosi).</div>
    </details>
</div>
