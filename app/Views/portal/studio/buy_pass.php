<?php use App\Helpers\Csrf; use App\Helpers\View; ?>

<div class="container-md py-3">
    <h4 class="mb-3">
        <i class="bi bi-cart-plus text-success me-2"></i>Kup karnet
    </h4>

    <?php if (empty($types)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> Klub nie udostępnił aktywnych karnetów do zakupu.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($types as $t): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= View::e($t['name']) ?></h5>
                            <small class="text-muted mb-2">
                                Sport: <strong><?= View::e($t['sport_key'] ?? 'wszystkie') ?></strong>
                                · Typ: <?= View::e($t['type']) ?>
                            </small>
                            <ul class="list-unstyled small mb-3">
                                <li>
                                    <i class="bi bi-ticket-perforated"></i>
                                    <?= $t['classes_count'] !== null
                                        ? (int)$t['classes_count'] . ' wejść'
                                        : 'Bez limitu wejść' ?>
                                </li>
                                <li><i class="bi bi-calendar"></i> Ważność: <?= (int)$t['validity_days'] ?> dni</li>
                            </ul>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="fs-5 text-primary">
                                        <?= number_format($t['price_cents'] / 100, 2, ',', ' ') ?>
                                        <small class="text-muted"><?= View::e($t['currency']) ?></small>
                                    </strong>
                                    <form action="<?= url('portal/studio/buy-pass') ?>" method="POST">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="pass_type_id" value="<?= (int)$t['id'] ?>">
                                        <button class="btn btn-success btn-sm" type="submit">
                                            <i class="bi bi-credit-card"></i> Kup
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
