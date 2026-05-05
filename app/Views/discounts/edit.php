<?php
use App\Helpers\View;
$d = $discount; // null = create mode
$isCreate = $d === null;
$action = $isCreate ? url('fees/discounts/store') : url('fees/discounts/' . (int)$d['id'] . '/update');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-<?= $isCreate ? 'plus-circle' : 'pencil-square' ?> text-primary me-2"></i>
        <?= $isCreate ? 'Nowa zniżka' : 'Edytuj zniżkę' ?>
    </h3>
    <a href="<?= url('fees/discounts') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Anuluj
    </a>
</div>

<div class="card p-4">
    <form method="POST" action="<?= $action ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Kod * <small class="text-muted">(unikalny)</small></label>
                <?php if ($isCreate): ?>
                    <input type="text" name="code" class="form-control" required maxlength="40"
                           pattern="[a-z0-9_-]+"
                           placeholder="np. junior, rodzinny, multisport">
                    <small class="text-muted">a-z, 0-9, _, -</small>
                <?php else: ?>
                    <input type="text" class="form-control" disabled value="<?= View::e($d['code']) ?>">
                    <small class="text-muted">Kod nie jest edytowalny po utworzeniu.</small>
                <?php endif; ?>
            </div>
            <div class="col-md-8">
                <label class="form-label">Nazwa *</label>
                <input type="text" name="name" class="form-control" required maxlength="120"
                       value="<?= View::e($d['name'] ?? '') ?>"
                       placeholder="np. Zniżka rodzinna -20%">
            </div>

            <div class="col-md-4">
                <label class="form-label">Typ zniżki *</label>
                <select name="discount_type" class="form-select" required>
                    <?php foreach ($types as $key => $label): ?>
                        <option value="<?= View::e($key) ?>"
                                <?= ($d['discount_type'] ?? 'percent') === $key ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Wartość *</label>
                <div class="input-group">
                    <input type="number" name="value" class="form-control"
                           step="0.01" min="0"
                           value="<?= View::e((string)($d['value'] ?? '')) ?>" required>
                    <span class="input-group-text" id="valueUnit">%</span>
                </div>
                <small class="text-muted">Procent (0-100) lub kwota PLN</small>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input type="hidden" name="is_stackable" value="0">
                    <input type="checkbox" name="is_stackable" value="1"
                           id="stackableChk" class="form-check-input"
                           <?= !empty($d['is_stackable'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="stackableChk">
                        <strong>Stackable</strong>
                        <span class="d-block text-muted small">
                            Łączy się z innymi zniżkami (np. junior + multi-sport)
                        </span>
                    </label>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Ważna od (opcjonalnie)</label>
                <input type="date" name="valid_from" class="form-control"
                       value="<?= View::e($d['valid_from'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Ważna do (opcjonalnie)</label>
                <input type="date" name="valid_to" class="form-control"
                       value="<?= View::e($d['valid_to'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Opis</label>
                <textarea name="description" class="form-control" rows="2"
                          placeholder="np. Rabat dla rodzin (>=2 członków). Aktywny cały rok."><?= View::e($d['description'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label">
                    Warunki auto-stosowania (opcjonalnie, JSON)
                </label>
                <textarea name="conditions_json" class="form-control font-monospace" rows="3"
                          placeholder='{"min_active_sports": 2}'><?= View::e($d['conditions'] ?? '') ?></textarea>
                <small class="text-muted">
                    Przykłady:
                    <code>{"min_active_sports": 2}</code> — multi-sport,
                    <code>{"age_max": 18}</code> — junior,
                    <code>{"family_min_members": 2}</code> — rodzinna,
                    <code>{"role": "scholarship"}</code> — stypendium.
                    Gdy puste — zniżka stosowana ręcznie.
                </small>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           id="activeChk" class="form-check-input"
                           <?= !empty($d['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="activeChk">
                        <strong>Zniżka aktywna</strong>
                        <span class="d-block text-muted small">
                            Nieaktywne zniżki są ukryte przy przypisywaniu do zawodników.
                        </span>
                    </label>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= url('fees/discounts') ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i>
                <?= $isCreate ? 'Utwórz zniżkę' : 'Zapisz zmiany' ?>
            </button>
        </div>
    </form>
</div>

<script>
// Aktualizuj jednostkę % vs PLN przy zmianie typu
(function() {
    const typeSelect = document.querySelector('select[name="discount_type"]');
    const unit = document.getElementById('valueUnit');
    if (!typeSelect || !unit) return;
    function update() {
        unit.textContent = typeSelect.value === 'percent' ? '%' : 'PLN';
    }
    typeSelect.addEventListener('change', update);
    update();
})();
</script>
