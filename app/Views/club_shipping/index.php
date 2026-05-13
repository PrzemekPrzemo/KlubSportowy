<?php
use App\Helpers\View;
$c = $config; // null jeśli nieskonfigurowana
$isConfigured = $c !== null;
$isActive = $isConfigured && !empty($c['is_active']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-truck text-primary me-2"></i>
        Wysyłka InPost
    </h3>
    <a href="<?= url('club/gateways') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Integracja InPost ShipX</strong> — Paczkomaty i Kurier InPost.
    Każdy klub używa <em>własnych</em> credentiali (token ShipX + organization_id) — pozwala
    korzystać z indywidualnej umowy handlowej i cennika. Dane wrażliwe szyfrowane AES-256-GCM.
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card h-100 <?= $isActive ? 'border-success' : '' ?>">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-box-seam text-primary fs-1 me-3"></i>
                    <div class="flex-grow-1">
                        <h5 class="mb-0">InPost ShipX</h5>
                        <?php if ($isConfigured): ?>
                            <?php if ($isActive): ?>
                                <span class="badge bg-success">Aktywna</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nieaktywna</span>
                            <?php endif; ?>
                            <?php if (!empty($c['is_sandbox'])): ?>
                                <span class="badge bg-warning text-dark">Sandbox</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-light text-secondary border">Nieskonfigurowana</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isConfigured): ?>
                    <ul class="list-unstyled small mb-3">
                        <li><strong>Organization ID:</strong>
                            <?php if (!empty($c['organization_id'])): ?>
                                <code><?= View::e(substr((string)$c['organization_id'], 0, 4)) ?>…</code>
                            <?php else: ?>
                                <span class="text-muted">brak</span>
                            <?php endif; ?>
                        </li>
                        <li><strong>API Token:</strong>
                            <?php if (!empty($c['api_token'])): ?>
                                <span class="text-success"><i class="bi bi-check2"></i> ustawiony</span>
                            <?php else: ?>
                                <span class="text-muted">brak</span>
                            <?php endif; ?>
                        </li>
                        <li><strong>Domyślny rozmiar:</strong> <?= View::e($c['default_size'] ?? 'A') ?></li>
                        <li><strong>Domyślny serwis:</strong> <code><?= View::e($c['default_service'] ?? 'inpost_locker_standard') ?></code></li>
                        <?php if (!empty($c['sender_name'])): ?>
                            <li><strong>Nadawca:</strong> <?= View::e($c['sender_name']) ?></li>
                        <?php endif; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small mb-3">
                        Skonfiguruj API token, aby tworzyć etykiety i nadawać przesyłki paczkomatowe.
                    </p>
                <?php endif; ?>

                <div class="d-flex gap-1">
                    <a href="<?= url('club/shipping/edit') ?>"
                       class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-<?= $isConfigured ? 'pencil' : 'plus-circle' ?>"></i>
                        <?= $isConfigured ? 'Edytuj' : 'Skonfiguruj' ?>
                    </a>
                    <?php if ($isConfigured): ?>
                        <form method="POST" action="<?= url('club/shipping/toggle') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-outline-<?= $isActive ? 'warning' : 'success' ?> btn-sm"
                                    title="<?= $isActive ? 'Dezaktywuj' : 'Aktywuj' ?>">
                                <i class="bi bi-<?= $isActive ? 'pause' : 'play' ?>"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isConfigured): ?>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="mb-2"><i class="bi bi-plug me-1"></i> Test połączenia</h6>
                <p class="small text-muted mb-2">Sprawdza czy API token jest prawidłowy (call /v1/points).</p>
                <form method="POST" action="<?= url('club/shipping/test') ?>" id="testForm">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-primary btn-sm" type="submit">
                        <i class="bi bi-plug"></i> Testuj połączenie
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<h5 class="mb-3"><i class="bi bi-clock-history me-1"></i> Ostatnie przesyłki</h5>
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Data</th>
                    <th>Odbiorca</th>
                    <th>Tracking</th>
                    <th>Rozmiar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shipments)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Brak przesyłek.</td></tr>
                <?php else: foreach ($shipments as $s): ?>
                    <tr>
                        <td><?= (int)$s['id'] ?></td>
                        <td class="small"><?= View::e((string)$s['created_at']) ?></td>
                        <td>
                            <?= View::e((string)($s['recipient_name'] ?? '—')) ?>
                            <?php if (!empty($s['recipient_email'])): ?>
                                <div class="small text-muted"><?= View::e($s['recipient_email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($s['tracking_number'])): ?>
                                <code><?= View::e($s['tracking_number']) ?></code>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::e((string)($s['size'] ?? '')) ?></td>
                        <td><span class="badge bg-secondary"><?= View::e((string)$s['status']) ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
