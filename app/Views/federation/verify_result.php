<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-3">
            <h5>Licencja</h5>
            <dl class="row small mb-0">
                <dt class="col-5">Numer</dt><dd class="col-7"><code><?= View::e($license['license_number']) ?></code></dd>
                <dt class="col-5">Typ</dt><dd class="col-7"><?= View::e($license['license_type']) ?></dd>
                <dt class="col-5">Ważna do</dt><dd class="col-7"><?= format_date($license['valid_until']) ?></dd>
                <dt class="col-5">Federacja</dt><dd class="col-7"><span class="badge bg-info"><?= View::e($fedCode) ?></span></dd>
            </dl>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <h5>Wynik weryfikacji</h5>
            <?php
            $statusBg = match($result['status'] ?? 'unknown') {
                'aktywna', 'active', 'valid' => 'success',
                'wygasla', 'expired' => 'danger',
                'connection_error', 'error' => 'warning',
                default => 'secondary',
            };
            ?>
            <div class="alert alert-<?= $statusBg ?> py-2">
                <strong>Status:</strong> <?= View::e($result['status'] ?? 'nieznany') ?>
            </div>
            <?php if (!empty($result['holder_name'])): ?>
                <div class="small"><strong>Posiadacz:</strong> <?= View::e($result['holder_name']) ?></div>
            <?php endif; ?>
            <?php if (!empty($result['valid_until'])): ?>
                <div class="small"><strong>Ważna do:</strong> <?= View::e($result['valid_until']) ?></div>
            <?php endif; ?>
            <?php if (!empty($result['message'])): ?>
                <div class="small text-muted mt-2"><?= View::e($result['message']) ?></div>
            <?php endif; ?>
            <?php if (!empty($result['verify_url']) || !empty($result['url'])): ?>
                <a href="<?= View::e($result['verify_url'] ?? $result['url']) ?>" target="_blank" class="btn btn-sm btn-outline-info mt-2">
                    <i class="bi bi-box-arrow-up-right"></i> Otwórz portal federacji
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="<?= url('federation') ?>" class="btn btn-outline-secondary">&larr; Powrót</a>
</div>
