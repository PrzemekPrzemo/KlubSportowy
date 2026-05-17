<?php use App\Helpers\View; ?>

<div class="container-md py-3">
    <h4 class="mb-3">
        <i class="bi bi-card-checklist text-primary me-2"></i>Moje karnety
    </h4>

    <div class="mb-3 d-flex gap-2 flex-wrap">
        <a href="<?= url('portal/studio/buy-pass') ?>" class="btn btn-success btn-sm">
            <i class="bi bi-cart-plus"></i> Kup karnet
        </a>
        <a href="<?= url('portal/studio/catalog') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-grid-3x3-gap"></i> Katalog klas
        </a>
    </div>

    <?php if (empty($passes)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Nie masz jeszcze żadnych karnetów.
            <a href="<?= url('portal/studio/buy-pass') ?>">Kup pierwszy karnet</a>.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($passes as $p): ?>
                <?php
                $statusBg = ['active' => 'border-success', 'exhausted' => 'border-secondary',
                             'expired' => 'border-warning', 'refunded' => 'border-danger'][$p['status']] ?? '';
                ?>
                <div class="col-md-6">
                    <div class="card shadow-sm <?= $statusBg ?>" style="border-width:2px;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6 class="card-title mb-1"><?= View::e($p['pass_name']) ?></h6>
                                <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= View::e($p['status']) ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                Sport: <strong><?= View::e($p['pass_sport'] ?? 'wszystkie') ?></strong>
                                · Typ: <?= View::e($p['pass_type']) ?>
                            </small>
                            <div class="mt-2">
                                <?php if ($p['classes_total'] !== null): ?>
                                    <strong><?= (int)$p['classes_remaining'] ?> / <?= (int)$p['classes_total'] ?></strong>
                                    <small class="text-muted">pozostałych wejść</small>
                                <?php else: ?>
                                    <strong>∞</strong> <small class="text-muted">wejść (open)</small>
                                <?php endif; ?>
                            </div>
                            <small class="d-block text-muted mt-1">
                                Ważny: <?= View::e($p['valid_from']) ?> → <?= View::e($p['valid_until']) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
