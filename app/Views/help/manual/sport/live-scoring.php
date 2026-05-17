<?php /** sport / live-scoring */ ?>
<p class="lead">Publiczna strona LIVE pozwala rodzicom, sponsorom i widzom sledzic wyniki turnieju w czasie rzeczywistym BEZ logowania. Wystarczy podzielic sie linkiem lub QR-em.</p>

<h2>Po co to wlaczac?</h2>
<ul>
    <li><strong>Rodzice na hali</strong> — kazdy moze podejrzec wyniki przez telefon bez zakladania konta.</li>
    <li><strong>Sponsorzy</strong> — kazde wyswietlenie to ekspozycja Twojego brandingu (logo + kolory klubu).</li>
    <li><strong>Marketing</strong> — link mozesz umiescic w social media i na plakatach (QR).</li>
</ul>

<h2>Jak wlaczyc</h2>
<ol>
    <li>Wejdz w turniej (<em>Turnieje &rarr; wybierz turniej</em>).</li>
    <li>W sekcji <strong>"Publiczne live scoring"</strong> kliknij <em>"Wlacz publiczne live"</em>.</li>
    <li>System wygeneruje globalnie unikalny link, np.:
        <br><code>https://app.clubdesk.pl/live/mistrzostwa-warszawy-bjj-2026-X7Y8Z9</code></li>
    <li>Skopiuj link lub pobierz QR (przycisk w sekcji) i udostepnij.</li>
</ol>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> QR na hali.</strong> Wydrukuj QR i powieś przy wejsciu na zawody — kazdy widz przez telefon zeskanuje i ma live wyniki na ekranie. Brandowane logo Twojego klubu na gorze!
</div>

<h2>Prywatnosc — co widza widzowie</h2>
<p>Z zasady minimalizujemy dane osobowe ujawniane publicznie:</p>
<ul>
    <li><strong>Default (rekomendowane):</strong> "Jan K." — imie + inicjal nazwiska. Idealne dla nieletnich.</li>
    <li><strong>Opt-in (pelne nazwiska):</strong> "Jan Kowalski". Zaznacz checkbox <em>"Pokazuj pelne nazwiska"</em> w sekcji live scoring. Uzywaj tylko po zebraniu zgod od pelnoletnich zawodnikow lub opiekunow nieletnich.</li>
</ul>

<p><strong>Co NIE jest pokazywane (nigdy):</strong> PESEL, data urodzenia, email, telefon, adres,
numer czlonkowski klubu, dane medyczne. Pokazywane sa wylacznie: imie (+ ew. nazwisko),
wyniki meczy, drabinka, klasyfikacja, logo i miasto klubu.</p>

<h2>Bezpieczenstwo</h2>
<ul>
    <li><strong>Rate-limit:</strong> 100 wyswietlen / minute na jeden adres IP (anti-scrape).</li>
    <li><strong>Audit:</strong> wszystkie wejscia loguja zhashowany IP (SHA-256) — bez plain-text.</li>
    <li><strong>Auto-update:</strong> przegladarka odbiera nowe wyniki przez SSE w 3 sekundy od wprowadzenia.</li>
    <li><strong>Mozna wylaczyc</strong> w kazdej chwili — link przestaje dzialac natychmiast (404).</li>
</ul>

<h2>Najczestsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy publikacja wynikow wymaga zgody zawodnikow?</summary>
        <p>Imie + inicjal nazwiska + wyniki sportowe sa dozwolone dla aktywnosci publicznych
        (turnieje, zawody) bez dodatkowej zgody. Dla pelnych nazwisk lub fotografii zalecamy
        wczesniej zebrac zgody (szczegolnie dla nieletnich — od opiekuna).</p>
    </details>
    <details>
        <summary>Co sie dzieje gdy wylacze publiczne live?</summary>
        <p>Link przestaje dzialac (HTTP 404). Slug pozostaje zapisany w bazie i moze zostac
        przywrocony przy ponownym wlaczeniu (ten sam URL).</p>
    </details>
    <details>
        <summary>Czy widzowie moga zobaczyc inne turnieje klubu?</summary>
        <p>Nie — strona pokazuje wylacznie ten konkretny turniej i nie linkuje do panelu klubu
        ani innych turniejow. Pełna izolacja.</p>
    </details>
    <details>
        <summary>Jakie wymagania techniczne dla widzow?</summary>
        <p>Dowolna nowoczesna przegladarka (Chrome/Safari/Firefox od 2017 r.) z JavaScript.
        Strona dziala mobile-first; QR mozna zeskanowac aparatem telefonu.</p>
    </details>
</div>
