<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Czym jest panel trenera w ClubDesk</h1>
<p class="lead">
    Panel trenera to wydzielona część platformy ClubDesk, zaprojektowana tak, aby
    codzienna praca trenera nie wymagała kontaktu z papierową listą obecności,
    arkuszami Excela ani prywatnymi grupami WhatsApp. Wszystko, czego potrzebujesz
    do prowadzenia zawodników, znajduje się w jednym miejscu i jest dostępne
    również z telefonu.
</p>

<h2>Co znajdziesz w panelu trenera</h2>
<p>
    Trener w ClubDesk odpowiada za <strong>grupę treningową</strong> (sekcję) —
    najczęściej jedna osoba prowadzi kilka grup wiekowych lub sportowych. Panel
    pokazuje wyłącznie te sekcje, do których jesteś przypisany jako prowadzący
    lub asystent. Dzięki temu nie musisz przeglądać setek członków klubu, żeby
    sprawdzić frekwencję na swoim wtorkowym treningu.
</p>

<p>Główne moduły dostępne dla trenera:</p>
<ul>
    <li><strong>Sekcje</strong> — lista grup, którymi się opiekujesz, wraz z zawodnikami w każdej z nich.</li>
    <li><strong>Harmonogram</strong> — kalendarz Twoich treningów, meczów i wydarzeń.</li>
    <li><strong>Obecności</strong> — zaznaczanie obecności jednym kliknięciem (z telefonu).</li>
    <li><strong>Turnieje</strong> — zgłaszanie zawodników i wpisywanie wyników.</li>
    <li><strong>Statystyki i ranking</strong> — postępy zawodników, rankingi sezonowe.</li>
    <li><strong>Prowizje</strong> — Twoje rozliczenie z klubem, raport wypłat.</li>
</ul>

<h2>Dla kogo jest ten manual</h2>
<p>
    Manuał napisaliśmy dla trenerów, którzy zaczynają pracę z ClubDesk — niezależnie
    od dyscypliny (piłka nożna, tenis, judo, pływanie, koszykówka). Wszystkie ekrany
    przedstawione w manuale to <em>poglądowe mockupy</em> — układ przycisków i tabel
    odpowiada rzeczywistej aplikacji, ale Twoje dane będą oczywiście inne. Mockupy
    są wyłączone z interakcji.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Jeśli prowadzisz jeszcze zawodników w kilku klubach, ClubDesk pozwala mieć jedno
    konto z dostępem do wielu organizacji — przełączasz klub w lewym górnym rogu.
</div>

<h2>Co odróżnia panel trenera od panelu zarządu</h2>
<p>
    W przeciwieństwie do zarządu klubu, trener <strong>nie widzi danych finansowych
    członków</strong> (kto ma zaległe składki, ile zapłacił). Widzi natomiast pełną
    sferę sportową: profil zawodnika, jego badania medyczne (data ważności — bez
    diagnoz), historię obecności i wyników, statystyki sezonu.
</p>

<p>
    Trener może też pisać wiadomości do rodziców i zawodników (bezpośrednio z poziomu
    listy obecności), ale nie ma dostępu do listy adresów e-mail całego klubu —
    tylko swoich sekcji. To celowe, podyktowane RODO.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/dashboard</div>
    <div class="manual-mockup-content">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Moje sekcje</div>
                        <div class="h3 mb-0">4</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Zawodnicy</div>
                        <div class="h3 mb-0">62</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Trening dzisiaj</div>
                        <div class="h3 mb-0">17:00</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body p-2 text-center">
                        <div class="text-muted small">Frekwencja (msc)</div>
                        <div class="h3 mb-0">84%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: dashboard trenera po zalogowaniu.</div>
</div>

<p>
    W następnych rozdziałach przeprowadzimy Cię krok po kroku przez pierwsze logowanie,
    konfigurację profilu i codzienne czynności na treningu.
</p>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
