<?php
$page = [
    'title'        => 'Zgody w imieniu dziecka',
    'category'     => 'Rodzic',
    'group'        => 'Moje dziecko',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Zgody w imieniu dziecka</h1>
<p class="lead">Twoje dziecko, dopóki jest niepełnoletnie, nie może samo wyrażać większości zgód prawnych dotyczących wykorzystania jego danych. To Ty, jako opiekun prawny, decydujesz o tym co klub może i czego nie może. Wszystkie zgody znajdziesz w jednym miejscu — i możesz je zmieniać w każdej chwili.</p>

<h2>Co to za zgody?</h2>
<p>Najczęstsze zgody, o które prosi klub:</p>
<ul>
    <li><strong>Zgoda na członkostwo</strong> — formalna podstawa do uczestnictwa Twojego dziecka w klubie.</li>
    <li><strong>Publikacja zdjęć i nagrań</strong> — z treningów, zawodów, na stronie klubu i social media.</li>
    <li><strong>Udostępnianie danych federacji</strong> — gdy klub zgłasza dziecko do związku sportowego (PESEL, data urodzenia).</li>
    <li><strong>Badania medyczne i ubezpieczenie</strong> — dla potrzeb dopuszczenia do zawodów.</li>
    <li><strong>Komunikacja marketingowa</strong> — newsletter, SMS-y o eventach klubowych.</li>
    <li><strong>Transport dziecka</strong> — zgoda na przewóz busem klubowym na zawody.</li>
</ul>

<h2>Gdzie zarządzać zgodami</h2>
<p>Wejdź w profil dziecka → zakładka <strong>Zgody</strong>. Każda zgoda to osobny suwak — włączasz lub wyłączasz, klikasz Zapisz.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/guardian/ward/142/consents</div>
    <div class="manual-mockup-content">
        <h5 class="mb-3"><i class="bi bi-shield-check text-success"></i> Zgody dla: Anna Kowalska (16 lat)</h5>
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Publikacja wizerunku</strong>
                        <p class="text-muted small mb-0">Zgoda na publikację zdjęć i wideo z udziału mojego dziecka w treningach i zawodach klubowych.</p>
                        <small class="text-muted">Wyrażona 02.09.2024 przez Maria Kowalska (matka)</small>
                    </div>
                    <input class="form-check-input form-switch ms-3" type="checkbox" checked style="transform:scale(1.5);">
                </div>
            </div>
        </div>
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Dane do federacji</strong>
                        <p class="text-muted small mb-0">Zgoda na przekazanie danych identyfikujących (PESEL, data urodzenia) do PZP w celu zgłoszeń do zawodów.</p>
                        <small class="text-muted">Wyrażona 02.09.2024</small>
                    </div>
                    <input class="form-check-input form-switch ms-3" type="checkbox" checked style="transform:scale(1.5);">
                </div>
            </div>
        </div>
        <div class="card mb-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Transport zbiorowy</strong>
                        <p class="text-muted small mb-0">Zgoda na przewóz dziecka busem klubowym na zawody wyjazdowe.</p>
                    </div>
                    <input class="form-check-input form-switch ms-3" type="checkbox" style="transform:scale(1.5);">
                </div>
            </div>
        </div>
        <button class="btn btn-success"><i class="bi bi-save"></i> Zapisz zmiany</button>
    </div>
    <div class="manual-mockup-caption">Każda zgoda z możliwością zmiany jednym kliknięciem.</div>
</div>

<h2>Jak wyrazić nową zgodę / zmienić istniejącą</h2>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź w <em>Profil dziecka → Zgody</em>.</li>
    <li><span class="manual-step-num">2</span>Przeczytaj treść zgody — szczególnie zwróć uwagę na kto i po co będzie korzystać z danych.</li>
    <li><span class="manual-step-num">3</span>Kliknij suwak — zielony oznacza wyrażoną zgodę.</li>
    <li><span class="manual-step-num">4</span>Kliknij <strong>Zapisz zmiany</strong>.</li>
    <li><span class="manual-step-num">5</span>System poprosi o potwierdzenie hasłem (dla bezpieczeństwa). Wpisz i potwierdź.</li>
    <li><span class="manual-step-num">6</span>Dostaniesz e-mail z potwierdzeniem zmiany.</li>
</ol>

<div class="manual-info">
    <strong>Historia zgód.</strong> Wszystkie Twoje zmiany są zapisywane. Klub przechowuje dowód, że to Ty (z dokładną datą i godziną) zmieniłeś(aś) zgodę. To wymóg prawa.
</div>

<h2>Zgody obowiązkowe vs. opcjonalne</h2>
<ul>
    <li><strong>Obowiązkowe</strong> (niemożliwe do wyłączenia): zgoda na podstawowe przetwarzanie danych członka, zgoda na członkostwo. Bez nich klub nie może w ogóle prowadzić zajęć.</li>
    <li><strong>Opcjonalne</strong>: publikacja zdjęć, marketing, transport itp. Dziecko może uczestniczyć w klubie nawet bez tych zgód.</li>
</ul>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Czy mogę wycofać zgodę po jakimś czasie?</summary>
    <p>Tak, w każdej chwili. To Twoje prawo. Działa od momentu wycofania (nie wstecz — np. zdjęcia już opublikowane mogą zostać, ale nowe nie powstaną).</p>
</details>
<details>
    <summary>Dziecko ma 15 lat — czy musi też wyrazić zgodę?</summary>
    <p>Według RODO art. 8 (Polska implementacja): poniżej 16 lat decyduje opiekun. Powyżej 16 lat dziecko może wyrażać zgody marketingowe samo, ale prawne (federacja, członkostwo) nadal wymagają Twojego podpisu.</p>
</details>
<details>
    <summary>Co jeśli mój małżonek/partnerka chce wycofać zgodę?</summary>
    <p>Jeśli oboje jesteście opiekunami prawnymi, każde z Was może niezależnie zmieniać zgody. Klub uznaje wycofanie zgody przez jedno z opiekunów za skuteczne.</p>
</details>
