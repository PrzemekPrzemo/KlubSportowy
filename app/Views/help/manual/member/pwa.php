<?php
$page = [
    'title'        => 'Instalacja jako aplikacja (PWA)',
    'category'     => 'Zawodnik',
    'group'        => 'Pierwsze kroki',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Instalacja jako aplikacja</h1>
<p class="lead">ClubDesk to nowoczesna „aplikacja w przeglądarce" (PWA — Progressive Web App). Możesz dodać ją do ekranu telefonu albo do pulpitu komputera i otwierać jednym tapnięciem — zupełnie jak Messengera czy Spotify, bez sklepu Google Play / App Store.</p>

<h2>Dlaczego warto zainstalować?</h2>
<ul>
    <li>Otwiera się natychmiast — bez wpisywania adresu w przeglądarce.</li>
    <li>Działa też offline (np. lista treningów, ostatnie ogłoszenia).</li>
    <li>Możesz dostawać powiadomienia push (przypomnienie o treningu, nowa wiadomość).</li>
    <li>Wygląda i zachowuje się jak normalna aplikacja — na pełnym ekranie.</li>
</ul>

<h2>Telefon (Android — Chrome)</h2>
<ol>
    <li><span class="manual-step-num">1</span>Otwórz portal w przeglądarce Chrome.</li>
    <li><span class="manual-step-num">2</span>Na górnej belce zobaczysz przycisk <strong>„Zainstaluj"</strong> z ikoną pobierania.</li>
    <li><span class="manual-step-num">3</span>Tapnij i potwierdź. Ikonka pojawi się na ekranie głównym telefonu.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/dashboard</div>
    <div class="manual-mockup-content">
        <nav class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#232232; color:#fff;">
            <div class="d-flex align-items-center gap-2">
                <div style="width:28px;height:28px;background:#EE2C28;border-radius:6px;"></div>
                <strong class="small">Portal zawodnika</strong>
            </div>
            <button class="btn btn-light btn-sm py-1 px-2">
                <i class="bi bi-download"></i> Zainstaluj
            </button>
        </nav>
        <div class="mt-3 p-3 border rounded bg-light text-center">
            <i class="bi bi-arrow-up-circle text-primary fs-3"></i>
            <p class="mb-0 small">Tapnij <strong>„Zainstaluj"</strong> w prawym górnym rogu, żeby dodać aplikację do ekranu telefonu.</p>
        </div>
    </div>
    <div class="manual-mockup-caption">Belka portalu z przyciskiem instalacji aplikacji.</div>
</div>

<h2>iPhone (Safari)</h2>
<ol>
    <li><span class="manual-step-num">1</span>Otwórz portal w przeglądarce <strong>Safari</strong> (musi być Safari, nie Chrome).</li>
    <li><span class="manual-step-num">2</span>Tapnij ikonkę udostępniania (kwadrat ze strzałką w górę) na dolnej belce.</li>
    <li><span class="manual-step-num">3</span>Z listy wybierz <strong>„Dodaj do ekranu początkowego"</strong>.</li>
    <li><span class="manual-step-num">4</span>Potwierdź nazwę (możesz zostawić „Portal zawodnika") i tapnij <em>Dodaj</em>.</li>
</ol>

<h2>Komputer (Chrome / Edge / Firefox)</h2>
<p>Na pasku adresu, po prawej stronie, pojawi się mała ikonka instalacji — kliknij ją i wybierz <em>Zainstaluj</em>. Aplikacja otworzy się w osobnym oknie bez pasków przeglądarki.</p>

<div class="manual-tip">
    <strong>Wskazówka.</strong> Jeśli przycisk „Zainstaluj" nie jest widoczny, to znaczy że portal już jest zainstalowany albo przeglądarka tego urządzenia nie wspiera PWA (np. Firefox na iOS). Możesz wtedy zapisać stronę jako skrót.
</div>

<h2>Włączenie powiadomień</h2>
<p>Po pierwszym otwarciu aplikacji przeglądarka zapyta, czy chcesz dostawać powiadomienia. Kliknij <strong>Zezwól</strong> — wtedy klub może Ci wysyłać przypomnienia o treningach, nowe ogłoszenia i wiadomości od trenera. Możesz to zmienić w każdej chwili w ustawieniach telefonu.</p>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Czy zainstalowanie aplikacji kosztuje?</summary>
    <p>Nie. Instalacja PWA jest darmowa i nie wymaga konta w sklepie z aplikacjami.</p>
</details>
<details>
    <summary>Ile zajmuje na telefonie?</summary>
    <p>Bardzo mało — kilka megabajtów. Dane (zdjęcia, treningi) ładują się dopiero gdy ich potrzebujesz.</p>
</details>
<details>
    <summary>Jak odinstalować?</summary>
    <p>Tak samo jak każdą inną aplikację — przytrzymaj ikonkę na ekranie i wybierz <em>Odinstaluj</em>.</p>
</details>
