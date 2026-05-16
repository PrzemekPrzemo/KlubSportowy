<?php
/**
 * Per-club KSeF settings form (zarzad/admin).
 *
 * @var array<string,mixed>|null $cfg
 * @var int                       $clubId
 */
use App\Helpers\View;

$hasToken = !empty($cfg['api_token_encrypted'] ?? null);
$hasCert  = !empty($cfg['cert_path'] ?? null);
$hasCertPw = !empty($cfg['cert_password_encrypted'] ?? null);
$mode     = (string)($cfg['mode'] ?? 'test');
$enabled  = (int)($cfg['enabled'] ?? 0) === 1;
$status   = (string)($cfg['last_connection_test_status'] ?? 'never');
$sCls     = match($status) { 'ok' => 'success', 'failed' => 'danger', default => 'secondary' };
$sLabel   = match($status) { 'ok' => 'OK', 'failed' => 'Błąd', default => 'Nie testowano' };
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i>KSeF — konfiguracja klubu</h3>
        <small class="text-muted">Krajowy System e-Faktur (Ministerstwo Finansów). Phase 1: foundation.</small>
    </div>
    <a href="<?= url('help/ksef') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-question-circle"></i> Pomoc
    </a>
</div>

<?php if ($flashSuccess ?? null): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= View::e($flashSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($flashError ?? null): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= View::e($flashError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($flashInfo ?? null): ?>
    <div class="alert alert-info alert-dismissible fade show"><?= View::e($flashInfo) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Status panel -->
<div class="card mb-3">
    <div class="card-body">
        <h6 class="text-muted text-uppercase small mb-3">Status integracji</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="small text-muted">Włączona przez admin platformy</div>
                <?php if ($enabled): ?>
                    <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Tak</span>
                <?php else: ?>
                    <span class="badge bg-secondary fs-6"><i class="bi bi-dash-circle me-1"></i>Nie</span>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Aktualny tryb</div>
                <span class="badge bg-<?= $mode === 'prod' ? 'warning text-dark' : 'info' ?> fs-6">
                    <?= strtoupper($mode) ?>
                </span>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Ostatni test połączenia</div>
                <span class="badge bg-<?= $sCls ?> fs-6"><?= $sLabel ?></span>
                <?php if (!empty($cfg['last_connection_test_at'])): ?>
                    <small class="d-block text-muted"><?= format_datetime($cfg['last_connection_test_at']) ?></small>
                <?php endif; ?>
                <?php if (!empty($cfg['last_connection_test_message'])): ?>
                    <small class="d-block text-muted"><?= View::e((string)$cfg['last_connection_test_message']) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-warning small">
    <i class="bi bi-shield-lock me-1"></i>
    <strong>Phase 1 — foundation.</strong> Konfiguracja przygotowuje klub do integracji z KSeF.
    Wystawianie i wysyłka faktur zostaną uruchomione w Phase 2/3. Test połączenia weryfikuje
    sieć + NIP klubu przez endpoint <code>AuthorisationChallenge</code>.
</div>

<form method="POST" action="<?= url('club/ksef-settings/update') ?>" enctype="multipart/form-data" class="card p-4 mb-3">
    <?= csrf_field() ?>

    <h5 class="mb-3">Identyfikacja klubu</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label">NIP klubu</label>
            <input type="text" name="nip" class="form-control font-monospace"
                   value="<?= View::e((string)($cfg['nip'] ?? '')) ?>"
                   pattern="\d{10}" maxlength="13"
                   placeholder="1234567890">
            <small class="text-muted">10 cyfr bez kresek. Walidacja sumy kontrolnej.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Tryb pracy</label>
            <div class="d-flex gap-3 pt-2">
                <div class="form-check">
                    <input type="radio" name="mode" id="modeTest" value="test"
                           class="form-check-input" <?= $mode === 'test' ? 'checked' : '' ?>>
                    <label for="modeTest" class="form-check-label">
                        <strong>TEST</strong> <small class="text-muted">(ksef-test.mf.gov.pl)</small>
                    </label>
                </div>
                <div class="form-check">
                    <input type="radio" name="mode" id="modeProd" value="prod"
                           class="form-check-input" <?= $mode === 'prod' ? 'checked' : '' ?>>
                    <label for="modeProd" class="form-check-label">
                        <strong>PROD</strong> <small class="text-muted">(ksef.mf.gov.pl)</small>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <h5 class="mb-3">
        Token KSeF
        <small class="text-muted fs-6">(szyfrowane AES-256-GCM, per-club HKDF)</small>
    </h5>
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <label class="form-label">Token autoryzacyjny</label>
            <input type="password" name="api_token" class="form-control font-monospace"
                   placeholder="<?= $hasToken ? '•••• •••• •••• ••••  (ustawiony — wpisz nowy aby zmienić)' : 'wprowadź token KSeF' ?>"
                   autocomplete="off">
            <small class="text-muted">
                Pusty = pozostaw aktualny.
                Token uzyskasz w panelu KSeF (<code>Ustawienia → Tokeny</code>) po zalogowaniu Profilem Zaufanym lub e-Dowodem.
            </small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Authorized subject identifier</label>
            <input type="text" name="authorized_subject_identifier" class="form-control font-monospace"
                   value="<?= View::e((string)($cfg['authorized_subject_identifier'] ?? '')) ?>"
                   maxlength="50" placeholder="opcjonalne">
            <small class="text-muted">Identifier dla SessionToken (opcjonalne).</small>
        </div>
    </div>

    <h5 class="mb-3">
        Certyfikat kwalifikowany .p12 / .pfx
        <small class="text-muted fs-6">(opcjonalne — wymagane do XAdES w Phase 3)</small>
    </h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label">Plik certyfikatu</label>
            <input type="file" name="cert_file" class="form-control" accept=".p12,.pfx">
            <small class="text-muted">
                <?php if ($hasCert): ?>
                    <i class="bi bi-check-circle text-success"></i> Certyfikat wgrany.
                    Wybierz nowy plik aby zastąpić.
                <?php else: ?>
                    Maks. 100KB. Plik zostanie zapisany w <code>storage/ksef/<?= (int)$clubId ?>/</code> z chmod 0600.
                <?php endif; ?>
            </small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Hasło do certyfikatu</label>
            <input type="password" name="cert_password" class="form-control"
                   placeholder="<?= $hasCertPw ? '•••• (ustawione)' : 'hasło z .p12' ?>"
                   autocomplete="off">
            <small class="text-muted">Pusty = pozostaw aktualne. Szyfrowane AES-256-GCM.</small>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?= url('dashboard') ?>" class="btn btn-outline-secondary">Anuluj</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i> Zapisz konfigurację
        </button>
    </div>
</form>

<!-- Test connection (osobny formularz / endpoint) -->
<div class="card p-3 bg-light">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="mb-1">Test połączenia z KSeF</h6>
            <small class="text-muted">
                Wywołuje <code>POST /online/Session/AuthorisationChallenge</code> z NIP klubu.
                Weryfikuje dostępność API + poprawność NIP w rejestrze KSeF.
            </small>
        </div>
        <form method="POST" action="<?= url('club/ksef-settings/test') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-plug"></i> Testuj połączenie
            </button>
        </form>
    </div>
</div>
