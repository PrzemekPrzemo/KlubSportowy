<?php
use App\Helpers\View;
$r = $rate;
$periods = [
    'monthly'   => 'Miesięcznie',
    'quarterly' => 'Kwartalnie',
    'yearly'    => 'Rocznie',
    'one_time'  => 'Jednorazowo',
];
$feeTypes = [
    'skladka'  => 'Składka członkowska',
    'wpisowe'  => 'Wpisowe',
    'licencja' => 'Licencja',
    'zawody'   => 'Zawody / Wyjazd',
    'obóz'     => 'Obóz / Zgrupowanie',
    'inne'     => 'Inne',
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-pencil-square text-primary me-2"></i>
        Edytuj stawkę opłat
    </h3>
    <a href="<?= url('fees/rates') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Anuluj
    </a>
</div>

<div class="card p-4">
    <form method="POST" action="<?= url('fees/rates/' . (int)$r['id'] . '/update') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Nazwa stawki *</label>
                <input type="text" name="name" class="form-control"
                       value="<?= View::e($r['name']) ?>" required maxlength="120"
                       placeholder="np. Składka miesięczna senior">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kwota (PLN) *</label>
                <input type="number" name="amount" class="form-control"
                       step="0.01" min="0"
                       value="<?= View::e((string)$r['amount']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Okres rozliczeniowy *</label>
                <select name="period" class="form-select" required>
                    <?php foreach ($periods as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $r['period'] === $key ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Typ opłaty *</label>
                <select name="fee_type" class="form-select" required>
                    <?php foreach ($feeTypes as $key => $label): ?>
                        <option value="<?= View::e($key) ?>" <?= $r['fee_type'] === $key ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Sport (opcjonalnie)</label>
                <select name="sport_id" class="form-select">
                    <option value="">— wszystkie sporty —</option>
                    <?php foreach ($sports as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"
                                <?= (int)$r['sport_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= View::e($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($classes)): ?>
            <div class="col-md-6">
                <label class="form-label">Klasa (opcjonalnie — w obrębie sportu)</label>
                <select name="class_id" class="form-select">
                    <option value="">— wszystkie klasy —</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"
                                <?= (int)$r['class_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= View::e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Stawka tylko dla tej klasy zawodników</small>
            </div>
            <?php endif; ?>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           id="rateActiveChk" class="form-check-input"
                           <?= !empty($r['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="rateActiveChk">
                        <strong>Aktywna</strong>
                        <span class="d-block text-muted small">
                            Nieaktywne stawki są ukryte na liście dodawania nowych opłat
                            ale historia płatności pozostaje.
                        </span>
                    </label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Opis (opcjonalnie)</label>
                <textarea name="description" class="form-control" rows="2"
                          placeholder="np. Stawka dla seniorów po 18. roku życia, płatna do 10-go każdego miesiąca"><?= View::e($r['description'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= url('fees/rates') ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i> Zapisz zmiany
            </button>
        </div>
    </form>
</div>
