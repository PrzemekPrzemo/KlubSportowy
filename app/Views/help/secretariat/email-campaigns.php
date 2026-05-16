<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>E-mail kampanie z szablonów</h1>
<p class="lead">
    Email kampanie to narzędzie do wysyłki tej samej (lub spersonalizowanej)
    wiadomości do dużej grupy członków klubu. Typowe scenariusze: zaproszenie na
    walne, ankiety satysfakcji, ogłoszenia o turnieju, kondolencje, życzenia
    świąteczne, kampanie sprzedażowe (sklep klubu).
</p>

<h2>Trzy typy kampanii</h2>
<ul>
    <li><strong>Informacyjna</strong> — jednorazowa wiadomość bez wymogu akcji.</li>
    <li><strong>Z linkiem do akcji</strong> — np. ankieta, formularz zapisu.</li>
    <li><strong>Spersonalizowana</strong> — z polami zmiennymi (imię, sekcja, saldo).</li>
</ul>

<h2>Tworzenie nowej kampanii</h2>
<p>
    W <strong>Korespondencja → Kampanie e-mail → + Nowa kampania</strong>
    przechodzisz przez 4 kroki:
</p>
<ol>
    <li><strong>Odbiorcy</strong> — wszyscy / wybrane sekcje / wynik filtra / wgrana lista.</li>
    <li><strong>Szablon</strong> — z biblioteki klubu lub od zera.</li>
    <li><strong>Treść</strong> — edytor z polami zmiennymi typu <code>{{imie}}</code>, <code>{{sekcja}}</code>.</li>
    <li><strong>Wysyłka</strong> — od razu lub z harmonogramem.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/campaigns/new?step=3</div>
    <div class="manual-mockup-content">
        <h6>Nowa kampania — krok 3/4: Treść</h6>
        <div class="progress mb-3" style="height:6px;"><div class="progress-bar bg-primary" style="width:75%"></div></div>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Temat</label>
                <input class="form-control mb-3" value="{{imie}}, zaproszenie na piknik klubowy 15 czerwca" disabled>
                <label class="form-label">Treść</label>
                <textarea class="form-control" rows="10" disabled>Cześć {{imie}}!

Zapraszamy całą rodzinę {{nazwisko_rodzinne}} na coroczny piknik klubowy
w sobotę 15 czerwca w godz. 14:00 – 19:00 na Orliku Centralnym.

W programie:
• mecze pokazowe sekcji {{sekcja}}
• grill, lemoniada
• konkursy dla dzieci i rodziców
• wręczenie pucharów za sezon

Zarezerwuj miejsce: [link do formularza]

Do zobaczenia!
Sekretariat klubu</textarea>
            </div>
            <div class="col-md-4">
                <h6 class="small text-muted">Dostępne zmienne</h6>
                <ul class="list-unstyled small font-monospace">
                    <li><code>{{imie}}</code></li>
                    <li><code>{{nazwisko}}</code></li>
                    <li><code>{{nazwisko_rodzinne}}</code></li>
                    <li><code>{{sekcja}}</code></li>
                    <li><code>{{trener}}</code></li>
                    <li><code>{{saldo}}</code></li>
                    <li><code>{{badania_do}}</code></li>
                </ul>
                <h6 class="small text-muted mt-3">Podgląd dla losowej osoby</h6>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Wyświetl</button>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: edytor treści kampanii z polami zmiennymi.</div>
</div>

<h2>Pola zmienne (personalizacja)</h2>
<p>
    Każdy odbiorca dostanie wiadomość z podstawionymi swoimi danymi:
    <code>{{imie}}</code> zostanie zastąpione "Antoni", <code>{{sekcja}}</code> —
    "Skrzaty U-9" itd. Lista dostępnych zmiennych jest pokazana po prawej
    stronie edytora. Klub może dodać własne zmienne (np. <code>{{kolor_sekcji}}</code>).
</p>

<h2>Test przed wysyłką</h2>
<p>
    Przycisk <em>"Wyświetl podgląd dla losowej osoby"</em> pokazuje, jak
    wiadomość będzie wyglądać po podstawieniu. Możesz też <em>"Wyślij test do
    siebie"</em> — system wyśle 1 egzemplarz na Twój adres e-mail, byś
    zobaczył render w prawdziwym kliencie pocztowym.
</p>

<h2>Harmonogram wysyłki</h2>
<p>
    Opcje:
</p>
<ul>
    <li><strong>Natychmiast</strong> — wysyłka startuje od razu po zatwierdzeniu.</li>
    <li><strong>O wybranej dacie/godzinie</strong> — np. piątek 09:00 (najlepsza
        skuteczność otwarć).</li>
    <li><strong>Falami</strong> — wysyłka rozłożona na 2-3 godziny, by nie
        zatkać serwera SMTP.</li>
</ul>

<h2>Statystyki kampanii</h2>
<p>
    Po wysłaniu w karcie kampanii zobaczysz:
</p>
<ul>
    <li>liczbę wysłanych vs zwróconych ("bounce");</li>
    <li>% otwartych (open rate);</li>
    <li>% klikniętych (click rate);</li>
    <li>listę wypisanych z newslettera (jeśli kampania ma cechę "marketing").</li>
</ul>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Kampanie do osób, które nie wyraziły zgody marketingowej (zgoda "newsletter"
    przy rejestracji), mogą być wyłącznie <em>informacyjne</em> (administracja
    klubu — np. odwołanie treningów, walne). Kampanie sprzedażowe wymagają
    zgody marketingowej. System sam pilnuje tej zasady.
</div>

<h2>Szablony klubu</h2>
<p>
    Najczęściej używane wiadomości warto zapisać jako szablony — <em>"Walne
    coroczne"</em>, <em>"Piknik letni"</em>, <em>"Ankieta sezonowa"</em>. Wystarczy
    raz w roku zmienić daty, treść zostanie. Szablony zapisuje administrator
    klubu — sekretariat ma do nich dostęp.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Najwyższy open rate (60-70%) dają kampanie wysyłane <strong>w sobotę przed
    południem</strong> i z imieniem odbiorcy w temacie. Najgorszy — w piątkowy
    wieczór (poniżej 25%).
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
