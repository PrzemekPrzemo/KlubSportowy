<?php use App\Helpers\Csrf; use App\Helpers\View; ?>

<h4 class="mb-3">
    <i class="bi bi-card-checklist text-primary me-2"></i>
    <?= View::e($title) ?> — <?= View::e($sportName) ?>
</h4>

<?php
$isEdit = !empty($row);
$action = $isEdit
    ? url('club/studio/' . $sport . '/pass-types/' . (int)$row['id'] . '/update')
    : url('club/studio/' . $sport . '/pass-types/store');
?>

<form action="<?= $action ?>" method="POST" class="card card-body shadow-sm">
    <?= Csrf::field() ?>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Kod (unikalny w klubie)</label>
            <input name="code" required maxlength="50" class="form-control font-monospace"
                   value="<?= View::e($row['code'] ?? $sport . '_') ?>">
        </div>
        <div class="col-md-8">
            <label class="form-label">Nazwa</label>
            <input name="name" required maxlength="200" class="form-control"
                   value="<?= View::e($row['name'] ?? '') ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Typ</label>
            <select name="type" class="form-select" onchange="document.getElementById('cc').disabled = (this.value === 'unlimited_period');">
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t ?>" <?= (($row['type'] ?? 'multi_class') === $t) ? 'selected' : '' ?>>
                        <?= View::e($t) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Liczba wejść</label>
            <input id="cc" type="number" name="classes_count" min="1" max="500" class="form-control"
                   value="<?= (int)($row['classes_count'] ?? 1) ?>"
                   <?= (($row['type'] ?? '') === 'unlimited_period') ? 'disabled' : '' ?>>
            <small class="text-muted">Pomijane dla typu unlimited_period</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Ważność (dni)</label>
            <input type="number" name="validity_days" min="1" max="365" class="form-control"
                   value="<?= (int)($row['validity_days'] ?? 30) ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">Cena (PLN)</label>
            <input type="number" step="0.01" name="price" min="0" class="form-control"
                   value="<?= number_format(($row['price_cents'] ?? 0) / 100, 2, '.', '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Sort order</label>
            <input type="number" name="sort_order" class="form-control"
                   value="<?= (int)($row['sort_order'] ?? 0) ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" name="active" id="active" class="form-check-input"
                       <?= ((int)($row['active'] ?? 1) === 1) ? 'checked' : '' ?>>
                <label for="active" class="form-check-label">Aktywny</label>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Zapisz</button>
        <a href="<?= url('club/studio/' . $sport . '/pass-types') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
