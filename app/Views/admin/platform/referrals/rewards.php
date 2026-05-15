<?php
use App\Helpers\Csrf;
use App\Helpers\View;
/** @var array $rewards */
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-gift me-2"></i> Konfiguracja rewardow</h1>
        <a href="<?= url('admin/platform/referrals') ?>" class="btn btn-link">
            <i class="bi bi-arrow-left"></i> Wroc do listy referrali
        </a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">Dodaj reward</h5></div>
        <div class="card-body">
            <form method="post" action="<?= url('admin/platform/referrals/rewards/store') ?>">
                <?= Csrf::field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nazwa</label>
                        <input name="name" class="form-control" required maxlength="120">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Typ</label>
                        <select name="reward_type" class="form-select">
                            <option value="discount">discount (%)</option>
                            <option value="months_free">months_free</option>
                            <option value="credit">credit (PLN)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Wartosc</label>
                        <input name="reward_value" type="number" step="0.01" min="0.01" required class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Opis</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Min. miesiecy</label>
                        <input name="min_paid_months" type="number" min="1" value="1" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max / referrer</label>
                        <input name="max_per_referrer" type="number" min="1" class="form-control" placeholder="bez limitu">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Wazny od</label>
                        <input name="valid_from" type="date" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Wazny do</label>
                        <input name="valid_until" type="date" class="form-control">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" value="1" id="is_active" checked class="form-check-input">
                            <label for="is_active" class="form-check-label">Aktywny</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Dodaj</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><h5 class="mb-0">Wszystkie rewardy</h5></div>
        <div class="card-body p-0">
            <?php if (empty($rewards)): ?>
                <div class="p-4 text-center text-muted">Brak rewardow.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nazwa</th>
                                <th>Typ</th>
                                <th>Wartosc</th>
                                <th>Min m-cy</th>
                                <th>Aktywny</th>
                                <th>Wazny</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rewards as $r): ?>
                                <tr>
                                    <td><?= (int)$r['id'] ?></td>
                                    <td><?= View::e($r['name']) ?></td>
                                    <td><code><?= View::e($r['reward_type']) ?></code></td>
                                    <td><?= View::e((string)$r['reward_value']) ?></td>
                                    <td><?= (int)$r['min_paid_months'] ?></td>
                                    <td>
                                        <?php if ((int)$r['is_active']): ?>
                                            <span class="badge bg-success">tak</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">nie</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= View::e((string)($r['valid_from'] ?? '-')) ?>
                                        →
                                        <?= View::e((string)($r['valid_until'] ?? '-')) ?>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= url('admin/platform/referrals/rewards/' . (int)$r['id'] . '/toggle') ?>" class="d-inline">
                                            <?= Csrf::field() ?>
                                            <button class="btn btn-sm btn-outline-secondary">
                                                <?= (int)$r['is_active'] ? 'Deaktywuj' : 'Aktywuj' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
