<?php
use App\Helpers\View;
$c = $config; // null jeśli nieskonfigurowana
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-shield-check text-primary me-2"></i>
        Federacja: <?= View::e($code) ?>
        <small class="text-muted fs-6"><?= View::e($label) ?></small>
    </h3>
    <a href="<?= url('club/federations') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<?php if ($isManual): ?>
    <div class="alert alert-warning small">
        <i class="bi bi-info-circle me-1"></i>
        Federacja <strong><?= View::e($code) ?></strong> nie ma dedykowanego adaptera —
        używamy fallbacku <code>GenericCsvExporter</code> (eksport CSV do ręcznego
        importu w panelu federacji).
    </div>
<?php endif; ?>

<div class="card p-4">
    <form method="POST" action="<?= url('club/federations/' . $code . '/save') ?>">
        <?= csrf_field() ?>

        <h5 class="mb-3">Tryb pracy</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="form-check form-switch border rounded p-3 ps-5">
                    <input type="hidden" name="is_sandbox" value="0">
                    <input type="checkbox" name="is_sandbox" value="1"
                           id="sandboxChk" class="form-check-input"
                           <?= !empty($c['is_sandbox'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="sandboxChk">
                        <strong>Sandbox (test)</strong>
                        <small class="d-block text-muted">
                            Tryb testowy (jeśli federacja udostępnia)
                        </small>
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch border rounded p-3 ps-5">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           id="activeChk" class="form-check-input"
                           <?= !empty($c['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activeChk">
                        <strong>Aktywna</strong>
                        <small class="d-block text-muted">
                            CLI runner będzie automatycznie eksportować zmiany
                        </small>
                    </label>
                </div>
            </div>
        </div>

        <h5 class="mb-3">Identyfikator klubu w federacji</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Numer klubu / Organization ID</label>
                <input type="text" name="organization_id" class="form-control"
                       value="<?= View::e($c['organization_id'] ?? '') ?>"
                       maxlength="60"
                       placeholder="np. numer klubu PZPN / PZSS / PZKosz">
                <small class="text-muted">Identyfikator klubu w systemie federacji (jeśli wymagany).</small>
            </div>
        </div>

        <h5 class="mb-3">
            API Credentials
            <small class="text-muted fs-6">(szyfrowane AES-256-GCM)</small>
        </h5>
        <div class="alert alert-warning small">
            <i class="bi bi-shield-lock me-1"></i>
            <strong>Wartości są szyfrowane przed zapisem do bazy.</strong>
            Pozostawienie pola pustego = zachowanie istniejącej wartości.
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Login klubu</label>
                <input type="text" name="api_username" class="form-control font-monospace"
                       placeholder="<?= !empty($c['api_username']) ? '••••• (ustawiony, wpisz nowy aby zmienić)' : 'login w systemie federacji' ?>"
                       autocomplete="off">
            </div>
            <div class="col-md-6">
                <label class="form-label">Hasło</label>
                <input type="password" name="api_password" class="form-control font-monospace"
                       placeholder="<?= !empty($c['api_password']) ? '••••• (ustawione)' : 'hasło' ?>"
                       autocomplete="off">
            </div>
            <div class="col-12">
                <label class="form-label">API Token (jeśli federacja udostępnia)</label>
                <input type="text" name="api_token" class="form-control font-monospace"
                       placeholder="<?= !empty($c['api_token']) ? '••••• (ustawiony)' : 'Bearer token / API key (opcjonalnie)' ?>"
                       autocomplete="off">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Notatki (wewnętrzne)</label>
            <textarea name="notes" class="form-control" rows="2"
                      maxlength="500"
                      placeholder="np. Konto klubu odnowione 2025-01-15"><?= View::e($c['notes'] ?? '') ?></textarea>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('club/federations') ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i> Zapisz konfigurację
            </button>
        </div>
    </form>
</div>

<?php if ($c !== null): ?>
<div class="card p-3 mt-3 bg-light">
    <h6 class="mb-2">Test połączenia</h6>
    <p class="small text-muted mb-2">
        Sanity check: czy credentials wyglądają poprawnie + ping portalu (jeśli dostępne).
    </p>
    <form method="POST" action="<?= url('club/federations/' . $code . '/test') ?>" id="testForm">
        <?= csrf_field() ?>
        <button class="btn btn-outline-primary btn-sm" type="submit">
            <i class="bi bi-plug"></i> Testuj
        </button>
    </form>
</div>
<?php endif; ?>
