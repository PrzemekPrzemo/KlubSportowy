<?php
$page = [
    'title'        => 'Wiadomości od trenera',
    'category'     => 'Zawodnik',
    'group'        => 'Komunikacja',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Wiadomości od trenera</h1>
<p class="lead">Trener może napisać do Ciebie prywatnie — wprost do portalu, bez SMS-ów i bez prywatnych numerów telefonu. Ty również możesz napisać do trenera, np. żeby zgłosić nieobecność albo zapytać o szczegóły treningu.</p>

<h2>Gdzie znaleźć wiadomości</h2>
<p>W menu portalu kliknij <strong>Wiadomości</strong> (ikonka dymka). Lewa kolumna pokazuje listę rozmów, prawa — treść aktualnie otwartej rozmowy. Działa jak komunikator (Messenger / WhatsApp), tylko w obrębie klubu.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/messages</div>
    <div class="manual-mockup-content">
        <div class="row g-0" style="border:1px solid #dee2e6; border-radius:.5rem; overflow:hidden;">
            <div class="col-4 border-end" style="background:#f8f9fa;">
                <div class="list-group list-group-flush">
                    <a class="list-group-item active">
                        <div class="d-flex w-100 justify-content-between">
                            <strong class="small">Jan Nowak (trener)</strong>
                            <small>14:32</small>
                        </div>
                        <small>OK, do zobaczenia w środę!</small>
                    </a>
                    <a class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <strong class="small">Sekretariat</strong>
                            <small>9 maja</small>
                        </div>
                        <small>Przypomnienie o badaniach…</small>
                    </a>
                </div>
            </div>
            <div class="col-8 d-flex flex-column" style="min-height:280px;">
                <div class="p-2 border-bottom small text-muted">
                    <i class="bi bi-person-circle"></i> Jan Nowak · trener pływania
                </div>
                <div class="p-3 flex-grow-1" style="background:#fff;">
                    <div class="text-start mb-2">
                        <span class="d-inline-block p-2 rounded bg-light small" style="max-width:80%;">
                            Cześć Aniu, dzisiaj nie dam rady przyjść na trening — dentysta. Czy mogę dołączyć w środę?
                        </span>
                        <div class="text-muted" style="font-size:.7rem;">14:30 · Ty</div>
                    </div>
                    <div class="text-end mb-2">
                        <span class="d-inline-block p-2 rounded text-white small" style="background:#0d6efd; max-width:80%;">
                            OK, do zobaczenia w środę! Daj znać czy potrzebujesz dodatkowej rozgrzewki na początek.
                        </span>
                        <div class="text-muted" style="font-size:.7rem;">14:32 · Jan Nowak</div>
                    </div>
                </div>
                <div class="p-2 border-top d-flex gap-2">
                    <input class="form-control form-control-sm" placeholder="Napisz wiadomość…">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-send"></i></button>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Wiadomości działają jak prosty komunikator. Twoje wiadomości po prawej, odpowiedzi po lewej.</div>
</div>

<h2>Wysłanie wiadomości</h2>
<ol>
    <li>Kliknij <strong>Nowa wiadomość</strong> (przycisk + w lewym górnym rogu).</li>
    <li>Wybierz odbiorcę z listy (trener, sekretariat, klub).</li>
    <li>Napisz treść — możesz dodać załącznik (zdjęcie, plik PDF, np. zaświadczenie lekarskie).</li>
    <li>Kliknij <em>Wyślij</em>. Odbiorca dostanie powiadomienie.</li>
</ol>

<h2>Do kogo można pisać</h2>
<ul>
    <li><strong>Twój trener</strong> — wszystko o treningach, nieobecnościach, planach.</li>
    <li><strong>Sekretariat klubu</strong> — sprawy administracyjne, składki, dokumenty.</li>
    <li><strong>Księgowość</strong> — faktury, rozliczenia, korekty.</li>
    <li><strong>Inni zawodnicy</strong> — tylko jeśli klub wyraźnie włączy tę opcję (domyślnie wyłączona dla bezpieczeństwa).</li>
</ul>

<div class="manual-tip">
    <strong>Historia.</strong> Wiadomości są zapisywane na zawsze i widoczne tylko dla Was dwojga. Trener nie może ich edytować ani usunąć po wysłaniu — to chroni obie strony rozmowy.
</div>

<h2>Statusy wiadomości</h2>
<ul>
    <li><i class="bi bi-check"></i> Wysłana — trafiła na serwer.</li>
    <li><i class="bi bi-check-all"></i> Doręczona — system zsynchronizował ją u adresata.</li>
    <li><i class="bi bi-check-all text-primary"></i> Przeczytana — adresat otworzył wiadomość.</li>
</ul>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Trener nie odpowiada — co dalej?</summary>
    <p>Trenerzy zwykle odpowiadają do 24h. Jeśli sprawa jest pilna (np. odwołanie zajęć w ostatniej chwili), zadzwoń lub napisz do sekretariatu klubu.</p>
</details>
<details>
    <summary>Czy mogę napisać do trenera z innej sekcji?</summary>
    <p>Tak — jeśli klub na to pozwala. W liście odbiorców zobaczysz wszystkich, do których masz uprawnienia napisania.</p>
</details>
<details>
    <summary>Czy zewnętrzni rodzice / opiekunowie widzą moje wiadomości?</summary>
    <p>Nie. Rozmowy są prywatne między Tobą a odbiorcą. Wyjątek: konto rodzica ma dostęp do wiadomości niepełnoletniego dziecka — bo prawnie odpowiada za nie.</p>
</details>
