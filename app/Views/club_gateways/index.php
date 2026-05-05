<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-credit-card text-primary me-2"></i>
        Bramki płatności
    </h3>
    <a href="<?= url('fees') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót do finansów
    </a>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Każdy klub może mieć własne API credentials</strong> dla bramek płatności.
    Pozwala to przyjmować wpłaty od członków bezpośrednio na konto klubu (zamiast przez globalne konto ClubDesk).
    Dane wrażliwe (API key, secret, CRC) są szyfrowane AES-256-GCM przed zapisem do bazy.
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($providers as $key => $label):
        $g = $existing[$key] ?? null;
        $isConfigured = $g !== null;
        $isActive     = $isConfigured && !empty($g['is_active']);
        $providerIcon = match($key) {
            'przelewy24' => 'bi-bank',
            'payu'       => 'bi-bank2',
            'stripe'     => 'bi-credit-card-2-front',
            'tpay'       => 'bi-cash-coin',
            'manual'     => 'bi-hand-thumbs-up',
            default      => 'bi-credit-card',
        };
    ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 <?= $isActive ? 'border-success' : '' ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi <?= $providerIcon ?> text-primary fs-1 me-3"></i>
                        <div class="flex-grow-1">
                            <h5 class="mb-0"><?= View::e($label) ?></h5>
                            <?php if ($isConfigured): ?>
                                <?php if ($isActive): ?>
                                    <span class="badge bg-success">Aktywna</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nieaktywna</span>
                                <?php endif; ?>
                                <?php if (!empty($g['is_sandbox'])): ?>
                                    <span class="badge bg-warning text-dark">Sandbox</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border">Nieskonfigurowana</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($isConfigured): ?>
                        <ul class="list-unstyled small mb-3">
                            <?php if (!empty($g['merchant_id'])): ?>
                                <li><strong>Merchant ID:</strong> <code><?= View::e($g['merchant_id']) ?></code></li>
                            <?php endif; ?>
                            <li><strong>Currency:</strong> <?= View::e($g['currency'] ?? 'PLN') ?></li>
                            <li><strong>API Key:</strong>
                                <?php if (!empty($g['api_key_masked'])): ?>
                                    <span class="text-success"><i class="bi bi-check2"></i> ustawiony</span>
                                <?php else: ?>
                                    <span class="text-muted">brak</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted small mb-3">
                            Skonfiguruj API credentials, aby przyjmować wpłaty przez tę bramkę.
                        </p>
                    <?php endif; ?>

                    <div class="d-flex gap-1">
                        <a href="<?= url('club/gateways/' . $key . '/edit') ?>"
                           class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-<?= $isConfigured ? 'pencil' : 'plus-circle' ?>"></i>
                            <?= $isConfigured ? 'Edytuj' : 'Skonfiguruj' ?>
                        </a>
                        <?php if ($isConfigured): ?>
                            <form method="POST" action="<?= url('club/gateways/' . $key . '/toggle') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-<?= $isActive ? 'warning' : 'success' ?> btn-sm"
                                        title="<?= $isActive ? 'Dezaktywuj' : 'Aktywuj' ?>">
                                    <i class="bi bi-<?= $isActive ? 'pause' : 'play' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= url('club/gateways/' . $key . '/delete') ?>"
                                  onsubmit="return confirm('Usunąć konfigurację? Wpłaty online przez tę bramkę przestaną działać.')"
                                  class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger btn-sm" title="Usuń">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
