<?php
use App\Helpers\View;
$c = $config; // null = nieskonfigurowana
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-truck text-primary me-2"></i>
        Konfiguracja: InPost
    </h3>
    <a href="<?= url('club/shipping') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card p-4">
    <form method="POST" action="<?= url('club/shipping/save') ?>">
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
                            Przesyłki testowe na sandbox-api-shipx-pl.easypack24.net
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
                            Możliwość tworzenia etykiet z poziomu klubu
                        </small>
                    </label>
                </div>
            </div>
        </div>

        <h5 class="mb-3">
            API Credentials
            <small class="text-muted fs-6">(szyfrowane AES-256-GCM)</small>
        </h5>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-shield-lock me-1"></i>
                    <strong>Wartości są szyfrowane przed zapisem do bazy.</strong>
                    Pozostawienie pola pustego = zachowanie istniejącej wartości.
                    Token wygenerujesz w <a href="https://manager.paczkomaty.pl" target="_blank" rel="noopener">manager.paczkomaty.pl</a>.
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Organization ID</label>
                <input type="text" name="organization_id" class="form-control font-monospace"
                       placeholder="<?= !empty($c['organization_id']) ? '••••• (ustawiony)' : 'np. 12345' ?>"
                       autocomplete="off">
                <small class="text-muted">Numer organizacji ShipX (widoczny w panelu InPost)</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">API Token</label>
                <input type="password" name="api_token" class="form-control font-monospace"
                       placeholder="<?= !empty($c['api_token']) ? '••••• (ustawiony)' : 'JWT/Bearer token' ?>"
                       autocomplete="off">
                <small class="text-muted">Bearer token uprawniony do zarządzania przesyłkami</small>
            </div>
        </div>

        <h5 class="mb-3">Domyślne ustawienia</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Domyślny rozmiar paczkomatu</label>
                <select name="default_size" class="form-select">
                    <?php foreach ($sizes as $k => $label): ?>
                        <option value="<?= View::e($k) ?>"
                            <?= ($c['default_size'] ?? 'A') === $k ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Domyślny serwis</label>
                <select name="default_service" class="form-select">
                    <?php foreach ($services as $k => $label): ?>
                        <option value="<?= View::e($k) ?>"
                            <?= ($c['default_service'] ?? 'inpost_locker_standard') === $k ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h5 class="mb-3">Dane nadawcy</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Nazwa nadawcy</label>
                <input type="text" name="sender_name" class="form-control"
                       value="<?= View::e($c['sender_name'] ?? '') ?>" maxlength="120"
                       placeholder="np. Klub Sportowy XYZ">
            </div>
            <div class="col-md-3">
                <label class="form-label">E-mail nadawcy</label>
                <input type="email" name="sender_email" class="form-control"
                       value="<?= View::e($c['sender_email'] ?? '') ?>" maxlength="120">
            </div>
            <div class="col-md-3">
                <label class="form-label">Telefon nadawcy</label>
                <input type="text" name="sender_phone" class="form-control"
                       value="<?= View::e($c['sender_phone'] ?? '') ?>" maxlength="20"
                       placeholder="9 cyfr">
            </div>
            <div class="col-md-6">
                <label class="form-label">Ulica</label>
                <input type="text" name="sender_address_street" class="form-control"
                       value="<?= View::e($c['sender_address_street'] ?? '') ?>" maxlength="120">
            </div>
            <div class="col-md-2">
                <label class="form-label">Nr budynku</label>
                <input type="text" name="sender_address_building" class="form-control"
                       value="<?= View::e($c['sender_address_building'] ?? '') ?>" maxlength="20">
            </div>
            <div class="col-md-2">
                <label class="form-label">Kod pocztowy</label>
                <input type="text" name="sender_address_post_code" class="form-control"
                       value="<?= View::e($c['sender_address_post_code'] ?? '') ?>" maxlength="10"
                       placeholder="00-000">
            </div>
            <div class="col-md-2">
                <label class="form-label">Miasto</label>
                <input type="text" name="sender_address_city" class="form-control"
                       value="<?= View::e($c['sender_address_city'] ?? '') ?>" maxlength="80">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('club/shipping') ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i> Zapisz konfigurację
            </button>
        </div>
    </form>
</div>
