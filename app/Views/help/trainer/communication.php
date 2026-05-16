<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Komunikacja z zawodnikiem i rodzicami</h1>
<p class="lead">
    ClubDesk daje trenerowi wbudowane narzędzie do komunikacji — e-mail i krótkie
    wiadomości push do aplikacji mobilnej. Zamiast trzymać kontakty rodziców w
    telefonie, korzystasz z modułu, który respektuje RODO i zostawia ślad
    audytowy.
</p>

<h2>Trzy kanały komunikacji</h2>
<ul>
    <li><strong>E-mail</strong> — dłuższa wiadomość, np. szczegóły turnieju, plan
        sezonu, prośba o dokumenty.</li>
    <li><strong>Push do aplikacji</strong> — krótkie, błyskawiczne powiadomienia,
        np. "Trening dziś odwołany — burza".</li>
    <li><strong>SMS</strong> — gdy rodzic nie ma zainstalowanej aplikacji i
        sytuacja jest pilna. SMS-y są limitowane planem klubu.</li>
</ul>

<h2>Wysłanie wiadomości do całej sekcji</h2>
<p>
    W zakładce <em>Zawodnicy</em> klikasz <strong>Wyślij wiadomość do grupy</strong>.
    Otwiera się prosty edytor z polem temat, treść i listą odbiorców (domyślnie
    wszyscy rodzice sekcji). Możesz odznaczyć pojedyncze osoby (np. tych, którzy
    już dostali tę informację osobiście).
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/trainer/sections/skrzaty-u9/message</div>
    <div class="manual-mockup-content">
        <h6>Nowa wiadomość — Skrzaty (U-9)</h6>
        <div class="mb-3">
            <label class="form-label">Kanał</label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" checked disabled>
                    <label class="form-check-label">E-mail</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" checked disabled>
                    <label class="form-check-label">Push (aplikacja)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" disabled>
                    <label class="form-check-label">SMS (limit: 27/100)</label>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Temat</label>
            <input type="text" class="form-control" value="Trening w środę 17:00 — pamiętamy o ochraniaczach" disabled>
        </div>
        <div class="mb-3">
            <label class="form-label">Treść</label>
            <textarea class="form-control" rows="4" disabled>Cześć Rodzice,
przypominam, że w środę gramy małe sparingi — proszę o pełen sprzęt i picie.
Pozdrawiam, trener Adam</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Odbiorcy (14)</label>
            <select multiple class="form-select" disabled style="height:120px;">
                <option selected>Marta Kowalska (Antoni)</option>
                <option selected>Tomasz Wójcik (Bartek)</option>
                <option selected>Agnieszka Nowak (Cezary)</option>
                <option selected>Magdalena Lewandowska (Dawid)</option>
                <option selected>… i 10 innych</option>
            </select>
        </div>
        <button class="btn btn-primary"><i class="bi bi-send"></i> Wyślij</button>
    </div>
    <div class="manual-mockup-caption">Mockup: kompozytor wiadomości grupowej. SMS zaznaczasz tylko gdy potrzeba — limit jest klubowy.</div>
</div>

<h2>Wiadomość do pojedynczego rodzica</h2>
<p>
    Z poziomu profilu zawodnika klikasz <strong>Wiadomość</strong> obok danych
    kontaktowych. Otwiera się okno czatu — historia wszystkich Waszych wiadomości
    jest zapisana po stronie ClubDesk. Możesz przeszukać ją po słowie kluczowym.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga RODO:</strong>
    Wszystkie wiadomości trenerów są przechowywane przez 24 miesiące i są dostępne
    dla zarządu klubu w trybie audytu (np. w przypadku skargi). Nie używaj tego
    kanału do tematów prywatnych ani do żartów, które mogą być źle zrozumiane.
</div>

<h2>Szablony wiadomości</h2>
<p>
    Sekretariat może zdefiniować <strong>szablony</strong> — przygotowane teksty,
    które wkleisz do edytora jednym kliknięciem. Najczęściej używane:
</p>
<ul>
    <li><em>Odwołanie treningu</em> (z pól: data, powód).</li>
    <li><em>Zaproszenie na turniej</em> (z pól: miejsce, data, koszt).</li>
    <li><em>Przypomnienie o badaniach</em> (z polem: data wygaśnięcia).</li>
</ul>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Jeżeli rodzic ma w preferencjach włączone "powiadomienia tylko push" — Twoja
    wiadomość e-mail nie zostanie wysłana. ClubDesk pokaże to w raporcie
    dostarczalności na liście wysłanych.
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
