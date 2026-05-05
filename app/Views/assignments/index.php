<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-people-fill text-primary me-2"></i>
        Subskrypcje opłat
    </h3>
    <div class="d-flex gap-2">
        <div class="btn-group" role="group">
            <a href="<?= url('fees') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-receipt"></i> Wpłaty
            </a>
            <a href="<?= url('fees/rates') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-cash-stack"></i> Stawki
            </a>
            <a href="<?= url('fees/discounts') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-percent"></i> Zniżki
            </a>
            <a href="<?= url('fees/assignments') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-people-fill"></i> Subskrypcje
            </a>
        </div>
        <a href="<?= url('fees/assignments/new') ?>" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle"></i> Przypisz opłatę
        </a>
    </div>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<form method="GET" class="card p-2 mb-3 d-flex flex-row gap-2 align-items-center">
    <label class="form-label mb-0 small text-muted">Status:</label>
    <select name="status" class="form-select form-select-sm" style="max-width: 220px"
            onchange="this.form.submit()">
        <option value="">— wszystkie —</option>
        <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= $key ?>" <?= $statusFilter === $key ? 'selected' : '' ?>>
                <?= View::e($label) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($statusFilter): ?>
        <a href="<?= url('fees/assignments') ?>" class="btn btn-link btn-sm">wyczyść filtr</a>
    <?php endif; ?>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Stawka</th>
                    <th class="text-end">Kwota</th>
                    <th>Okres</th>
                    <th>Ważność</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($assignments)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">
                    Brak subskrypcji.
                    <a href="<?= url('fees/assignments/new') ?>">Przypisz pierwszą</a>.
                </td></tr>
            <?php else: foreach ($assignments as $a): ?>
                <tr>
                    <td>
                        <strong><?= View::e($a['last_name']) ?> <?= View::e($a['first_name']) ?></strong>
                        <small class="d-block text-muted">#<?= View::e($a['member_number'] ?? '?') ?></small>
                    </td>
                    <td>
                        <?= View::e($a['rate_name']) ?>
                        <small class="d-block text-muted"><?= View::e($a['rate_fee_type'] ?? '') ?></small>
                    </td>
                    <td class="text-end font-monospace">
                        <?= format_money($a['rate_amount']) ?>
                    </td>
                    <td><span class="badge bg-light text-secondary border"><?= View::e($a['rate_period'] ?? '?') ?></span></td>
                    <td class="small text-muted">
                        <?= View::e($a['valid_from']) ?>
                        →
                        <?= View::e($a['valid_to'] ?? '∞') ?>
                    </td>
                    <td>
                        <?php
                            $badgeClass = match($a['status']) {
                                'active'    => 'success',
                                'suspended' => 'warning',
                                'ended'     => 'secondary',
                                default     => 'secondary',
                            };
                        ?>
                        <span class="badge bg-<?= $badgeClass ?>">
                            <?= View::e($statuses[$a['status']] ?? $a['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= url('fees/assignments/' . (int)$a['id'] . '/edit') ?>"
                               class="btn btn-sm btn-outline-primary" title="Edytuj">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="<?= url('fees/assignments/' . (int)$a['id'] . '/delete') ?>"
                                  onsubmit="return confirm('Usunąć subskrypcję? Operacja nieodwracalna.')" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Usuń">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
