<?php
$page = [
    'title'        => 'Co to jest portal zawodnika',
    'category'     => 'Zawodnik',
    'group'        => 'Pierwsze kroki',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Co to jest portal zawodnika</h1>
<p class="lead">Portal zawodnika to Twoja własna przestrzeń w klubie — wszystko, co Cię dotyczy, w jednym miejscu. Bez papierków, bez SMS-ów do trenera, bez „kiedy mam zapłacić?".</p>

<p>Portal działa w przeglądarce (komputer, telefon, tablet) i możesz go zainstalować jak zwykłą aplikację. Logujesz się raz, a potem masz pod ręką wszystko: kalendarz treningów, składki, wyniki turniejów, zgody RODO i kontakt z klubem.</p>

<h2>Co znajdziesz w portalu</h2>
<ul>
    <li><strong>Pulpit (Dashboard)</strong> — najważniejsze informacje na dziś: najbliższy trening, status składek, nowe ogłoszenia.</li>
    <li><strong>Mój profil</strong> — dane osobowe, zdjęcie, dokumenty.</li>
    <li><strong>Kalendarz</strong> — Twoje treningi, mecze, turnieje.</li>
    <li><strong>Obecność</strong> — historia frekwencji i statystyki.</li>
    <li><strong>Składki</strong> — co masz do zapłaty i jak zapłacić online.</li>
    <li><strong>Wyniki i osiągnięcia</strong> — Twoje starty, miejsca, odznaki.</li>
    <li><strong>Ogłoszenia</strong> — komunikaty z klubu i wiadomości od trenera.</li>
    <li><strong>RODO</strong> — Twoje zgody i prawa.</li>
</ul>

<h2>Jak wygląda pulpit</h2>
<p>Tak będzie wyglądał Twój ekran zaraz po zalogowaniu:</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/dashboard</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Cześć, Anno! <span class="text-muted">👋</span></h5>
                <small class="text-muted">UKS Iskra · Sekcja: Pływanie</small>
            </div>
            <span class="badge bg-success">Aktywny zawodnik</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body">
                        <small class="text-muted">Najbliższy trening</small>
                        <div class="fw-bold">Pon. 18 maja, 17:00</div>
                        <small>Basen miejski · trener Kowalski</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body">
                        <small class="text-muted">Składka — maj</small>
                        <div class="fw-bold text-warning">120 zł do zapłaty</div>
                        <a class="btn btn-sm btn-warning mt-1">Zapłać teraz</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body">
                        <small class="text-muted">Frekwencja w tym miesiącu</small>
                        <div class="fw-bold">88% <i class="bi bi-arrow-up text-success"></i></div>
                        <small>7 z 8 treningów</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Pulpit zawodnika — wszystko, co najważniejsze, widać od razu.</div>
</div>

<div class="manual-tip">
    <strong>Pro tip.</strong> Jeśli widzisz na pulpicie czerwoną plakietkę „Wymaga uwagi" — kliknij ją, system dokładnie powie, co trzeba zrobić.
</div>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Czy muszę być w klubie, żeby coś tutaj zrobić?</summary>
    <p>Nie. Portal działa wszędzie tam, gdzie masz internet. Możesz sprawdzić plan zajęć w autobusie, zapłacić składkę z kanapy, a po treningu zobaczyć obecność.</p>
</details>
<details>
    <summary>Czy moi rodzice też mają tutaj dostęp?</summary>
    <p>Jeśli jesteś niepełnoletni(a), rodzic ma osobne konto opiekuna z dostępem do Twojego profilu. Dorosły zawodnik korzysta z portalu sam.</p>
</details>
<details>
    <summary>Czy to bezpieczne?</summary>
    <p>Tak. Twoje dane są zaszyfrowane, dostęp tylko po zalogowaniu, możesz dodatkowo włączyć weryfikację dwuetapową (2FA). Klub przestrzega RODO.</p>
</details>
