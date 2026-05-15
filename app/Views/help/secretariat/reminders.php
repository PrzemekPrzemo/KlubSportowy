<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Przypomnienia o zaległościach (e-mail / SMS)</h1>
<p class="lead">
    Przypomnienia są codziennością sekretariatu. Dobry rytm przypomnień (nie za
    nachalny, nie za rzadki) potrafi obniżyć poziom zaległości w klubie o 60–80%.
    ClubDesk oferuje automatyzację, ale Ty decydujesz, kiedy i jak ostro
    przypominać.
</p>

<h2>Trzy poziomy przypomnień</h2>
<ol>
    <li><strong>Łagodne przypomnienie</strong> (3 dni po terminie) — automatyczne, w
        tonie "może mailem do spamu trafiło, zerknij proszę". Tylko e-mail.</li>
    <li><strong>Formalne przypomnienie</strong> (10 dni po terminie) — automatyczne,
        z dołączoną fakturą PDF. E-mail + push (jeśli rodzic ma aplikację).</li>
    <li><strong>Wezwanie do zapłaty</strong> (21 dni po terminie) — nie
        automatyczne. Sekretariat decyduje, czy wysłać. Zawiera adnotację o
        możliwych konsekwencjach (np. wstrzymanie udziału w turniejach).</li>
</ol>

<h2>Konfiguracja automatycznych poziomów</h2>
<p>
    Pierwsze dwa poziomy mogą wysyłać się automatycznie (każdej nocy system
    sprawdza zaległości i wysyła przypomnienia). Konfigurację (czy włączone,
    dni od terminu, treść) zarządza ClubDesk w <em>Finanse → Ustawienia
    przypomnień</em>. Sekretariat ma podgląd, ale nie modyfikuje.
</p>

<h2>Masowe przypomnienie ręczne</h2>
<p>
    Z listy zaległych faktur klikasz <strong>"Wyślij masowe przypomnienie"</strong>.
    Otwiera się okno wyboru: kanał (e-mail / SMS / oba), szablon (łagodny /
    formalny / wezwanie), filtr odbiorców (wszyscy zaległościowi / tylko
    rodzice z aplikacją / itp.). Klikasz <em>Wyślij</em> i system zaczyna
    rozsyłkę.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/secretariat/invoices/reminders/send</div>
    <div class="manual-mockup-content">
        <h6>Masowe przypomnienie — 18 zaległościowców</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Kanał</label>
                <div class="form-check"><input type="checkbox" class="form-check-input" checked disabled><label class="form-check-label">E-mail (18)</label></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">SMS (limit: 27/100 mc)</label></div>
                <div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">Push (12 ma aplikację)</label></div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Szablon</label>
                <select class="form-select" disabled>
                    <option>Łagodne przypomnienie</option>
                    <option selected>Formalne przypomnienie</option>
                    <option>Wezwanie do zapłaty</option>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Treść (preview)</label>
            <div class="border p-2 rounded bg-light small">
                Szanowna Pani Marto,<br>
                przypominamy, że faktura FV-2026/04/138 na kwotę 280 zł jest zaległa od 29 dni.<br>
                Prosimy o uregulowanie należności do 18 maja 2026.<br>
                Link do płatności: [auto]<br><br>
                Z pozdrowieniami,<br>
                Sekretariat <em>klub</em>
            </div>
        </div>
        <div class="d-flex gap-2 justify-content-end">
            <button class="btn btn-outline-secondary">Podgląd dla 1 osoby</button>
            <button class="btn btn-warning">Wyślij do 18 osób</button>
        </div>
    </div>
    <div class="manual-mockup-caption">Mockup: kompozytor masowych przypomnień. Każda wiadomość jest spersonalizowana.</div>
</div>

<h2>SMS przypomnienia</h2>
<p>
    SMS jest najbardziej skuteczny, ale kosztuje (klub ma miesięczny limit). Używaj
    SMS-ów dla:
</p>
<ul>
    <li>najtrudniejszych zaległościowców (&gt;30 dni);</li>
    <li>rodziców, którzy nie mają aplikacji i nie czytają e-maili;</li>
    <li>wezwań do zapłaty (poziom 3).</li>
</ul>

<h2>Telefony "lista do telefonu"</h2>
<p>
    Czasem najlepiej zadzwonić. Przycisk <em>"Lista do telefonu"</em> generuje
    arkusz z imionami, kwotami i numerami telefonów. Możesz wydrukować i
    krok po kroku odhaczać po rozmowach. Każda notatka po telefonie zostaje
    zapisana w karcie członka.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Zachowuj spokojny, profesjonalny ton w przypomnieniach. Nawet rodzic, który
    zalegał 60 dni, jest wartością dla klubu — agresywne wezwanie często
    powoduje rezygnację z członkostwa. Większość zaległości to przeoczenia,
    nie zła wola.
</div>

<h2>Statystyki skuteczności</h2>
<p>
    W <em>Finanse → Statystyki przypomnień</em> widzisz, jak długo średnio
    zaległościowcy płacą po każdym z trzech poziomów, jaki procent w ogóle
    płaci, jaki procent przekracza 60 dni. Te liczby pomagają zarządowi
    konfigurować polityki klubu.
</p>

<div class="manual-tip">
    <strong><i class="bi bi-lightbulb"></i> Wskazówka:</strong>
    Przypomnienia są <em>zsyłane raz na poziom</em> — jeżeli ktoś dostał już
    łagodne przypomnienie 5 dni temu, nie dostanie kolejnego łagodnego (chyba
    że ręcznie zresetujesz licznik).
</div>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
