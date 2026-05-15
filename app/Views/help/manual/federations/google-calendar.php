<?php /** federations / google-calendar */ ?>
<p class="lead">Integracja z Google Calendar pozwala eksportować plan klubu (treningi, turnieje, wydarzenia) do kalendarza Google — Twojego, członków, sponsorów. Subskrypcja jest jednokierunkowa, więc edycja w Google nie zaśmieca ClubDesk.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Wejdź w <strong>Klub → Integracje → Google Calendar → Połącz</strong>.</li>
    <li>Autoryzuj OAuth — wybierz konto Google i zatwierdź uprawnienia (read+write w wybranym kalendarzu).</li>
    <li>Wybierz, które typy wydarzeń synchronizować: treningi, mecze, turnieje, zebrania.</li>
    <li>Wybierz filtr po sekcjach (np. tylko własna sekcja dla trenera).</li>
    <li>Zapisz — pierwszy sync może potrwać 5-10 min.</li>
</ol>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> Linki iCal.</strong> Alternatywnie udostępniaj link iCal (<code>.ics</code>) — kompatybilny z Apple Calendar, Outlook, Mozilla Thunderbird, bez wymogu OAuth.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy zawodnicy widzą swoje treningi w Google Calendar?</summary>
        <div class="faq-body">Tak — każdy ma własny link iCal w sekcji <em>Mój kalendarz → Eksport</em>.</div>
    </details>
    <details>
        <summary>Co z prywatnością?</summary>
        <div class="faq-body">Synchronizowane są tylko zdarzenia, do których członek ma uprawnienie w ClubDesk.</div>
    </details>
    <details>
        <summary>Czy mogę odłączyć?</summary>
        <div class="faq-body">Tak — w dowolnej chwili. ClubDesk usunie tokeny OAuth, ale historyczne wpisy w Google Calendar pozostają (możesz je usunąć ręcznie).</div>
    </details>
</div>
