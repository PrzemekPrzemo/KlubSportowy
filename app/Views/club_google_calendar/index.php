<?php
/** @var array<string,mixed>|null $config */
use App\Helpers\View;

$c = $config;
$isConfigured = $c !== null;
$hasTokens    = $isConfigured && !empty($c['access_token_enc']);
$isActive     = $isConfigured && !empty($c['is_active']);
$lastSync     = $isConfigured ? ($c['last_sync_at'] ?? null) : null;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-google text-primary me-2"></i>
        Synchronizacja Google Calendar
    </h3>
    <a href="<?= url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Per-klub OAuth2 + Calendar API v3.</strong>
    Klub łączy własne konto Google (Workspace lub Gmail) — wydarzenia z modułu
    Kalendarz są automatycznie synchronizowane do wskazanego kalendarza Google.
    Tokeny szyfrowane AES-256-GCM. Cron 15-minutowy: <code>cli/google_calendar_sync.php</code>.
</div>

<?php if ($flashSuccess ?? null): ?>
    <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError ?? null): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100 <?= $hasTokens ? 'border-success' : '' ?>">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-google fs-1 text-primary me-3"></i>
                    <div class="flex-grow-1">
                        <h5 class="mb-0">Status połączenia</h5>
                        <?php if ($hasTokens): ?>
                            <span class="badge bg-success">Połączone</span>
                            <?php if ($isActive): ?>
                                <span class="badge bg-primary">Aktywne</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Wyłączone</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-light text-secondary border">Niepołączone</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($hasTokens): ?>
                    <ul class="list-unstyled small mb-3">
                        <li><strong>Konto Google:</strong>
                            <?php if (!empty($c['google_account_email'])): ?>
                                <code><?= View::e($c['google_account_email']) ?></code>
                            <?php else: ?>
                                <span class="text-muted">nieznane</span>
                            <?php endif; ?>
                        </li>
                        <li><strong>Calendar ID:</strong>
                            <code><?= View::e($c['calendar_id'] ?: 'primary') ?></code>
                        </li>
                        <li><strong>Kierunek sync:</strong>
                            <?php
                            $dirLabels = ['push' => 'Push (klub → Google)', 'pull' => 'Pull (Google → klub)', 'both' => 'Dwukierunkowy'];
                            ?>
                            <?= View::e($dirLabels[$c['sync_direction']] ?? $c['sync_direction']) ?>
                        </li>
                        <li><strong>Ostatni sync:</strong>
                            <?= $lastSync ? View::e($lastSync) . ' — ' . View::e((string)($c['last_sync_status'] ?? '')) : '<span class="text-muted">nigdy</span>' ?>
                        </li>
                        <?php if (!empty($c['last_sync_message'])): ?>
                            <li><small class="text-muted"><?= View::e($c['last_sync_message']) ?></small></li>
                        <?php endif; ?>
                        <li><strong>Token wygasa:</strong>
                            <?php if (!empty($c['token_expires_at'])): ?>
                                <?= View::e($c['token_expires_at']) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small mb-3">
                        Połącz konto Google, aby zacząć synchronizować wydarzenia kalendarza klubu.
                    </p>
                <?php endif; ?>

                <div class="d-flex gap-2 flex-wrap">
                    <?php if (!$hasTokens): ?>
                        <a href="<?= url('club/google-calendar/connect') ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-google"></i> Połącz z Google Calendar
                        </a>
                    <?php else: ?>
                        <a href="<?= url('club/google-calendar/connect') ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-clockwise"></i> Połącz ponownie
                        </a>
                        <form method="POST" action="<?= url('club/google-calendar/sync-now') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-success btn-sm">
                                <i class="bi bi-arrow-repeat"></i> Sync teraz
                            </button>
                        </form>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="gcalTestBtn">
                            <i class="bi bi-plug"></i> Test
                        </button>
                        <form method="POST" action="<?= url('club/google-calendar/disconnect') ?>" class="d-inline"
                              onsubmit="return confirm('Odłączyć konto Google? Tokeny zostaną usunięte.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-x-circle"></i> Odłącz
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div id="gcalTestResult" class="small mt-2"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-sliders me-1"></i> Ustawienia sync</h6>
                <form method="POST" action="<?= url('club/google-calendar/save') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Calendar ID</label>
                        <input type="text" name="calendar_id" class="form-control form-control-sm"
                               value="<?= View::e($c['calendar_id'] ?? 'primary') ?>"
                               placeholder="primary lub np. xyz@group.calendar.google.com">
                        <div class="form-text">
                            <code>primary</code> = główny kalendarz konta. Inny calendarId pobierzesz z UI Google Calendar.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kierunek synchronizacji</label>
                        <?php $dir = $c['sync_direction'] ?? 'push'; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="sync_direction" id="dirPush" value="push" <?= $dir === 'push' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="dirPush">
                                <strong>Push</strong> — wydarzenia klubu → Google
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="sync_direction" id="dirPull" value="pull" <?= $dir === 'pull' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="dirPull">
                                <strong>Pull</strong> — Google → wydarzenia klubu
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="sync_direction" id="dirBoth" value="both" <?= $dir === 'both' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="dirBoth">
                                <strong>Dwukierunkowy</strong>
                            </label>
                        </div>
                    </div>

                    <details class="mb-3">
                        <summary class="small text-muted">Zaawansowane: własny OAuth client (white-label)</summary>
                        <div class="mt-2">
                            <div class="mb-2">
                                <label class="form-label small">OAuth Client ID</label>
                                <input type="text" name="client_id" class="form-control form-control-sm"
                                       value="<?= View::e($c['client_id'] ?? '') ?>"
                                       placeholder="pusty = globalny z config/google.php">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">OAuth Client Secret</label>
                                <input type="password" name="client_secret" class="form-control form-control-sm"
                                       value="" placeholder="<?= !empty($c['client_secret_enc']) ? 'ustawiony — wpisz aby zmienić' : 'pusty = globalny' ?>">
                            </div>
                        </div>
                    </details>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                               value="1" <?= $isActive ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive">
                            Sync włączony (cron co 15 min)
                        </label>
                    </div>

                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="bi bi-save"></i> Zapisz ustawienia
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h6 class="mb-2"><i class="bi bi-info-circle me-1"></i> Setup Google Cloud Console</h6>
        <ol class="small mb-0">
            <li>Wejdź w <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a> i utwórz nowy projekt.</li>
            <li>APIs &amp; Services → Library → włącz <strong>Google Calendar API</strong>.</li>
            <li>Credentials → Create Credentials → <strong>OAuth client ID</strong> (Web application).</li>
            <li>Authorized redirect URI: <code><?= View::e((defined('BASE_URL') ? rtrim(BASE_URL, '/') : '') . '/club/google-calendar/callback') ?></code></li>
            <li>Pobierz <code>client_id</code> + <code>client_secret</code> i ustaw zmienne środowiskowe
                <code>GOOGLE_OAUTH_CLIENT_ID</code> / <code>GOOGLE_OAUTH_CLIENT_SECRET</code>
                (lub wpisz w sekcji "Zaawansowane" powyżej dla white-label).</li>
            <li>OAuth consent screen — dodaj scope <code>https://www.googleapis.com/auth/calendar</code> i swoje konta jako test users.</li>
        </ol>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('gcalTestBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var out = document.getElementById('gcalTestResult');
        out.innerHTML = '<span class="text-muted">Testuję…</span>';
        var fd = new FormData();
        fd.append('_csrf', '<?= csrf_token() ?>');
        fetch('<?= url('club/google-calendar/test') ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
            .then(function (res) {
                if (res.body.success) {
                    out.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> ' +
                        (res.body.message || 'OK') + '</span>';
                } else {
                    out.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' +
                        (res.body.message || 'Błąd') + '</span>';
                }
            })
            .catch(function (e) {
                out.innerHTML = '<span class="text-danger">Sieć: ' + e.message + '</span>';
            });
    });
})();
</script>
