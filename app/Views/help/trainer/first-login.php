<?php
/** @var array $page @var array $manualNav @var ?array $prev @var ?array $next */
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Pierwsze logowanie i dashboard</h1>
<p class="lead">
    Po tym, jak sekretariat klubu założy Ci konto trenera, otrzymasz wiadomość
    e-mail z linkiem aktywacyjnym. Link jest jednorazowy i ważny 48 godzin — jeśli
    przegapisz to okno, poproś sekretariat o ponowne wysłanie zaproszenia.
</p>

<h2>Krok 1 — Ustawienie hasła</h2>
<p>
    Po kliknięciu w link trafisz na ekran ustawienia hasła. Polityka klubu może
    wymagać minimum 10 znaków, w tym jedną cyfrę i znak specjalny. Hasło powinieneś
    znać tylko Ty — administrator klubu też go nie widzi.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/auth/activate/abc123</div>
    <div class="manual-mockup-content">
        <form>
            <div class="mb-3">
                <label class="form-label">Hasło</label>
                <input type="password" class="form-control" placeholder="Min. 10 znaków" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Powtórz hasło</label>
                <input type="password" class="form-control" disabled>
            </div>
            <button type="button" class="btn btn-primary">Aktywuj konto</button>
        </form>
    </div>
    <div class="manual-mockup-caption">Mockup: ekran aktywacji konta trenera.</div>
</div>

<h2>Krok 2 — Logowanie i 2FA</h2>
<p>
    Po aktywacji wracasz na stronę logowania pod adresem
    <code>app.clubdesk.pl</code>. Wpisujesz e-mail i hasło. Jeśli zarząd Twojego klubu
    włączył wymóg dwuskładnikowego uwierzytelniania (2FA), system poprosi o kod
    z aplikacji Google Authenticator lub Authy.
</p>

<div class="manual-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Uwaga:</strong>
    Konta z dostępem do danych medycznych zawodników (a trener taki dostęp ma) niemal
    zawsze mają wymagane 2FA. Przygotuj telefon przed pierwszym logowaniem.
</div>

<h2>Krok 3 — Dashboard</h2>
<p>
    Pierwszy ekran po zalogowaniu to <strong>dashboard trenera</strong>. Zobaczysz
    cztery kafelki z najważniejszymi liczbami: liczbę sekcji, którymi się opiekujesz,
    łączną liczbę zawodników, najbliższy trening i Twoją średnią frekwencję
    z ostatnich 30 dni. Poniżej znajdziesz <strong>oś czasu</strong> z nadchodzącymi
    wydarzeniami: treningami, meczami, turniejami i terminami badań Twoich zawodników.
</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar">app.clubdesk.pl/dashboard</div>
    <div class="manual-mockup-content">
        <h6 class="mb-3">Witaj, trenerze Adamie!</h6>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Sekcje</small><div class="h4 mb-0">4</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Zawodnicy</small><div class="h4 mb-0">62</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Najbliższy trening</small><div class="h4 mb-0">dziś 17:00</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card bg-light"><div class="card-body p-2 text-center"><small class="text-muted">Frekwencja</small><div class="h4 mb-0">84%</div></div></div></div>
        </div>
        <h6 class="mt-3">Nadchodzące</h6>
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between"><span>Dziś 17:00 — Skrzaty (U-9)</span><span class="badge bg-primary">Trening</span></li>
            <li class="list-group-item d-flex justify-content-between"><span>Jutro 19:00 — Junior (U-15)</span><span class="badge bg-primary">Trening</span></li>
            <li class="list-group-item d-flex justify-content-between"><span>Sob 09:00 — Turniej Bielsko</span><span class="badge bg-success">Turniej</span></li>
        </ul>
    </div>
    <div class="manual-mockup-caption">Mockup: dashboard trenera z osią czasu nadchodzących wydarzeń.</div>
</div>

<h2>Krok 4 — Profil i preferencje</h2>
<p>
    Zanim zaczniesz pracę z grupami, sprawdź <strong>swój profil</strong> (ikona
    użytkownika w prawym górnym rogu → "Mój profil"). Upewnij się, że masz poprawny
    numer telefonu — to z niego zostaniesz oddzwoniony, jeśli któryś z rodziców
    będzie próbował się skontaktować przez przycisk "Zadzwoń do trenera".
</p>

<p>
    W zakładce <em>Preferencje</em> ustawisz powiadomienia (e-mail, push) i tryb
    ciemny aplikacji. Jeżeli pracujesz głównie z telefonu, włącz instalację PWA —
    ClubDesk doda się jako aplikacja na ekranie głównym.
</p>

<?php include __DIR__ . '/../_layout_manual_footer.php'; ?>
