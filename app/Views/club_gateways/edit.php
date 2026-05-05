<?php
use App\Helpers\View;
$c = $config; // null jeśli nieskonfigurowana
$isCreate = $c === null;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-credit-card text-primary me-2"></i>
        <?= View::e($providerLabel) ?>
    </h3>
    <a href="<?= url('club/gateways') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card p-4">
    <form method="POST" action="<?= url('club/gateways/' . $provider . '/save') ?>">
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
                            Płatności testowe — zalecane przed produkcją
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
                            Bramka dostępna do przyjmowania wpłat online od członków
                        </small>
                    </label>
                </div>
            </div>
        </div>

        <h5 class="mb-3">Identyfikator handlowca</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Merchant ID / POS ID</label>
                <input type="text" name="merchant_id" class="form-control"
                       value="<?= View::e($c['merchant_id'] ?? '') ?>"
                       maxlength="120"
                       placeholder="np. 123456 (Przelewy24) lub acct_xxx (Stripe)">
            </div>
            <div class="col-md-6">
                <label class="form-label">Waluta</label>
                <input type="text" name="currency" class="form-control text-uppercase"
                       value="<?= View::e($c['currency'] ?? 'PLN') ?>"
                       pattern="[A-Z]{3}" maxlength="3">
                <small class="text-muted">3 litery (ISO 4217)</small>
            </div>
        </div>

        <h5 class="mb-3">
            API Credentials
            <small class="text-muted fs-6">(szyfrowane AES-256-GCM)</small>
        </h5>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="alert alert-warning small">
                    <i class="bi bi-shield-lock me-1"></i>
                    <strong>Wartości są szyfrowane przed zapisem do bazy.</strong>
                    Pozostawienie pola pustego = zachowanie istniejącej wartości.
                    Wpisanie nowej wartości = nadpisanie. Aby usunąć — wpisz spację.
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Key</label>
                <input type="text" name="api_key" class="form-control font-monospace"
                       placeholder="<?= !empty($c['api_key']) ? '••••• (ustawiony, wpisz nowy aby zmienić)' : 'wprowadź klucz' ?>"
                       autocomplete="off">
            </div>
            <div class="col-md-6">
                <label class="form-label">API Secret</label>
                <input type="password" name="api_secret" class="form-control font-monospace"
                       placeholder="<?= !empty($c['api_secret']) ? '••••• (ustawiony)' : 'wprowadź sekret' ?>"
                       autocomplete="off">
            </div>
            <?php if ($provider === 'przelewy24'): ?>
            <div class="col-md-6">
                <label class="form-label">CRC Key (Przelewy24)</label>
                <input type="text" name="crc_key" class="form-control font-monospace"
                       placeholder="<?= !empty($c['crc_key']) ? '••••• (ustawiony)' : 'CRC z panelu P24' ?>"
                       autocomplete="off">
            </div>
            <?php endif; ?>
            <div class="col-md-6">
                <label class="form-label">Webhook Secret</label>
                <input type="password" name="webhook_secret" class="form-control font-monospace"
                       placeholder="<?= !empty($c['webhook_secret']) ? '••••• (ustawiony)' : 'sekret do weryfikacji webhook-ów' ?>"
                       autocomplete="off">
            </div>
        </div>

        <h5 class="mb-3">URL-e callback'ów</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Return URL (po opłaceniu)</label>
                <input type="url" name="return_url" class="form-control"
                       value="<?= View::e($c['return_url'] ?? '') ?>"
                       maxlength="255"
                       placeholder="<?= View::e(BASE_URL) ?>/portal/payments/success">
            </div>
            <div class="col-md-6">
                <label class="form-label">Notify URL (webhook IPN)</label>
                <input type="url" name="notify_url" class="form-control"
                       value="<?= View::e($c['notify_url'] ?? '') ?>"
                       maxlength="255"
                       placeholder="<?= View::e(BASE_URL) ?>/api/v1/payment/webhook">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Notatki (wewnętrzne)</label>
            <textarea name="notes" class="form-control" rows="2"
                      placeholder="np. Główne konto klubu, salda przelewane co tydzień"><?= View::e($c['notes'] ?? '') ?></textarea>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('club/gateways') ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i> Zapisz konfigurację
            </button>
        </div>
    </form>
</div>

<?php if (!$isCreate && !empty($c['is_active'])): ?>
<div class="card p-3 mt-3 bg-light">
    <h6 class="mb-2">Test połączenia</h6>
    <p class="small text-muted mb-2">Zweryfikuj że credentials są poprawne (sanity check).</p>
    <form method="POST" action="<?= url('club/gateways/' . $provider . '/test') ?>" id="testForm">
        <?= csrf_field() ?>
        <button class="btn btn-outline-primary btn-sm" type="submit">
            <i class="bi bi-plug"></i> Testuj
        </button>
    </form>
</div>
<?php endif; ?>
