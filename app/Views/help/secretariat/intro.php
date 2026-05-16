<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Rola sekretariatu w ClubDesk</h1>
<p class="lead">
    Sekretariat to "centrum operacyjne" klubu — to przez Ciebie przepływają
    rejestracje nowych członków, faktury, dokumenty RODO, zaświadczenia,
    korespondencja masowa. ClubDesk daje sekretariatowi szereg narzędzi, które
    redukują pracę ręczną o 70–80% w porównaniu do prowadzenia klubu w
    Excelu i Wordzie.
</p>

<h2>Co robi sekretariat na co dzień</h2>
<ul>
    <li>Rejestruje nowych członków klubu (dorosłych i dzieci).</li>
    <li>Aktualizuje dane członkowskie (przeprowadzki, zmiana nazwisk, kontaktów).</li>
    <li>Generuje faktury i pilnuje płatności.</li>
    <li>Pilnuje terminów ważności badań i przedłuża licencje.</li>
    <li>Pisze do członków klubu — informacje, kampanie, przypomnienia.</li>
    <li>Wystawia zaświadczenia i drukuje umowy.</li>
    <li>Pilnuje zgodności z RODO (zgody, prawa dostępu, retencja).</li>
</ul>

<h2>Co odróżnia sekretariat od zarządu</h2>
<p>
    Sekretariat wykonuje operacje, ale nie ustala polityk: nie ustala wysokości
    składek, nie zmienia regulaminu, nie zatrudnia trenerów. To zadania zarządu.
    Niemniej sekretariat ma dostęp do <strong>najszerszego zakresu danych
    osobowych członków klubu</strong> — dlatego praca w tej roli wymaga zaufania
    i dyscypliny w zakresie ochrony danych.
</p>

<h2>Co odróżnia sekretariat od księgowego</h2>
<p>
    Sekretariat <em>wystawia</em> faktury i widzi statusy płatności, ale <em>nie
    księguje</em> przelewów i nie tworzy raportów do urzędu skarbowego. To
    granica między rolami — księgowy ma dostęp do "konta" klubu, sekretariat
    do "klienta" klubu.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    W małych klubach jedna osoba może łączyć role sekretariatu i księgowego.
    ClubDesk pozwala na to (multi-rola), ale logi audytowe rozdzielają operacje
    "sekretariackie" od "księgowych" — to ważne w razie kontroli.
</div>

<h2>Dla kogo jest ten manual</h2>
<p>
    Manuał napisany został dla pracownika sekretariatu klubu sportowego, który
    właśnie zaczyna pracę w ClubDesk. Zakładamy podstawową umiejętność obsługi
    komputera (Excel, e-mail) i znajomość terminologii klubowej (członek,
    składka, sekcja). Nie zakładamy znajomości IT.
</p>

<h2>Konwencje w manuale</h2>
<p>
    Wszystkie ekrany pokazane na obrazach są <strong>mockupami</strong> — układ
    graficzny jest zgodny z aplikacją, ale dane (imiona, kwoty, daty) są
    fikcyjne. Mockupy są <em>nieklikalne</em>. Faktyczne ekrany w Twoim panelu
    mogą się nieznacznie różnić zależnie od konfiguracji klubu (branding,
    aktywne moduły).
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga RODO:</strong>
    Praca w sekretariacie oznacza dostęp do danych osobowych. ClubDesk loguje
    każdą operację — kto, kiedy, jakie dane podejrzał lub zmienił. W razie
    audytu RODO klub musi przedstawić te logi. Pracuj z poszanowaniem zasady
    "minimum potrzebne, minimum widziane".
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
