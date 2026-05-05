<?php
use App\Helpers\View;
$r = $rate;
$isCreate = $r === null;
$action   = $isCreate
    ? url('club/trainers/commissions/rates/store')
    : url('club/trainers/commissions/rates/' . (int)$r['id'] . '/update');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-<?= $isCreate ? 'plus-circle' : 'pencil-square' ?> text-primary me-2"></i>
        <?= $isCreate ? 'Nowa stawka prowizji' : 'Edytuj stawkę' ?>
    </h3>
    <a href="<?= url('club/trainers/commissions/rates') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Anuluj
    </a>
</div>

<div class="card p-4">
    <form method="POST" action="<?= $action ?>">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Trener *</label>
                <?php if ($isCreate): ?>
                    <select name="trainer_user_id" class="form-select" required>
                        <option value="">— wybierz —</option>
                        <?php foreach ($trainers as $t): ?>
                            <option value="<?= (int)$t['id'] ?>">
                                <?= View::e($t['full_name'] ?? $t['username']) ?> (@<?= View::e($t['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" class="form-control" disabled
                           value="<?= View::e($r['trainer_name'] ?? '#' . $r['trainer_user_id']) ?>">
                    <small class="text-muted">Trener nie jest edytowalny — utwórz nowy wpis dla innego trenera.</small>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">Sport <small class="text-muted">(puste = wszystkie sporty)</small></label>
                <?php if ($isCreate): ?>
                    <select name="sport_id" class="form-select">
                        <option value="">— wszystkie sporty (klubowa) —</option>
                        <?php foreach ($sports as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= View::e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" class="form-control" disabled
                           value="<?= View::e($r['sport_id'] ? '#' . $r['sport_id'] : 'wszystkie') ?>">
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label">Typ stawki *</label>
                <select name="commission_type" class="form-select" required>
                    <?php foreach ($types as $key => $label): ?>
                        <option value="<?= View::e($key) ?>"
                                <?= ($r['commission_type'] ?? 'percent') === $key ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Wartość *</label>
                <input type="number" step="0.01" min="0" name="value" class="form-control" required
                       value="<?= View::e($r['value'] ?? '') ?>"
                       placeholder="np. 30 (% lub PLN)">
            </div>

            <div class="col-md-4">
                <label class="form-label">Dotyczy *</label>
                <select name="applies_to" class="form-select" required>
                    <?php foreach ($appliesTo as $key => $label): ?>
                        <option value="<?= View::e($key) ?>"
                                <?= ($r['applies_to'] ?? 'skladka') === $key ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Ważne od</label>
                <input type="date" name="valid_from" class="form-control"
                       value="<?= View::e($r['valid_from'] ?? date('Y-m-d')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Ważne do <small class="text-muted">(puste = bezterminowo)</small></label>
                <input type="date" name="valid_to" class="form-control"
                       value="<?= View::e($r['valid_to'] ?? '') ?>">
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                           <?= ($r['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label for="is_active" class="form-check-label">Aktywna</label>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Notatki</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="np. promocja noworoczna, umowa B2B nr 2026/01"><?= View::e($r['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> <?= $isCreate ? 'Utwórz stawkę' : 'Zapisz zmiany' ?>
            </button>
        </div>
    </form>
</div>
