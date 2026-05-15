<?php /** Plan subskrypcji i płatności */ ?>
<p class="lead">ClubDesk działa w modelu SaaS — opłata abonamentowa pokrywa hosting, kopie zapasowe, aktualizacje i wsparcie. Plan dobierasz w zależności od liczby członków klubu i potrzebnych funkcji.</p>

<h2>Dostępne plany</h2>
<table class="table table-bordered align-middle">
    <thead class="table-light">
        <tr><th>Plan</th><th>Limit członków</th><th>Płatności online</th><th>Federacje</th><th>Cena/mies.</th></tr>
    </thead>
    <tbody>
        <tr><td><strong>Free</strong></td><td>do 25</td><td>—</td><td>—</td><td>0 zł</td></tr>
        <tr><td><strong>Starter</strong></td><td>do 100</td><td>1 bramka</td><td>1 PZ</td><td>49 zł</td></tr>
        <tr><td><strong>Pro</strong></td><td>do 500</td><td>wszystkie</td><td>wszystkie</td><td>149 zł</td></tr>
        <tr><td><strong>Enterprise</strong></td><td>bez limitu</td><td>wszystkie + custom</td><td>wszystkie + API</td><td>indywidualnie</td></tr>
    </tbody>
</table>

<h2>Zmiana planu</h2>
<ol>
    <li>Przejdź do <strong>Ustawienia → Subskrypcja</strong>.</li>
    <li>Wybierz nowy plan z listy i kliknij <em>Zmień plan</em>.</li>
    <li>System wyliczy proporcjonalną dopłatę (lub kredyt przy downgrade) i wystawi fakturę.</li>
    <li>Po opłaceniu nowe limity są aktywne natychmiast.</li>
</ol>

<div class="manual-callout manual-callout-tip">
    <strong>Roczna płatność = 2 miesiące gratis.</strong> Wybierając rozliczenie roczne, otrzymujesz rabat 16,7% (równowartość 2 miesięcy). Idealne dla klubów planujących długoterminowo.
</div>

<h2>Metody płatności</h2>
<p>Abonament rozliczamy przez Stripe (karta kredytowa/debetowa) lub Przelewy24 (BLIK, przelew bankowy, P24Now). Dla planów Enterprise dostępna jest płatność przelewem na podstawie faktury proforma. Recurring billing automatycznie pobiera środki w dniu odnowienia — system poinformuje Cię 7 i 3 dni przed pobraniem.</p>

<h2>Faktury i księgowanie</h2>
<p>Faktury VAT generowane są automatycznie po opłaceniu i dostępne w <em>Ustawienia → Faktury</em>. Każda faktura zawiera dane sprzedawcy (ClubDesk sp. z o.o.) i kupującego (Twój klub, dane z <em>Ustawienia → Dane do faktur</em>). Faktury możesz pobrać w PDF i jako XML JPK_FA dla księgowości.</p>

<h2>Co się stanie, jeśli przekroczę limit?</h2>
<p>System wyświetli ostrzeżenie po przekroczeniu 90% limitu i zablokuje dodawanie nowych członków po przekroczeniu 100%. Istniejące dane pozostają nienaruszone — wystarczy zmienić plan lub usunąć nieaktywne konta. Przez 14 dni od przekroczenia masz tzw. grace period bez blokad.</p>

<h2>Anulowanie subskrypcji</h2>
<div class="manual-callout manual-callout-warn">
    Anulowanie nie usuwa danych natychmiast. Po zakończeniu okresu rozliczeniowego konto przechodzi w tryb <em>read-only</em> przez 30 dni, a następnie dane są usuwane (zgodnie z RODO). W każdej chwili możesz przed upływem 30 dni reaktywować subskrypcję.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details><summary>Czy mogę spróbować planu Pro za darmo?</summary><div class="faq-body">Tak — pierwszy miesiąc Pro jest darmowy dla nowych klubów. Karta nie jest wymagana podczas rejestracji testu.</div></details>
    <details><summary>Czy faktury są zgodne z polskim VAT?</summary><div class="faq-body">Tak — wystawiamy zgodnie z ustawą o VAT (Dz.U. 2004 nr 54 poz. 535), z podziałem netto/VAT/brutto. Klub z UE poza Polską otrzymuje fakturę z VAT 0% (reverse charge).</div></details>
    <details><summary>Czy mogę zmienić plan w trakcie miesiąca?</summary><div class="faq-body">Tak, w dowolnym momencie. System wylicza proporcjonalną opłatę.</div></details>
</div>
