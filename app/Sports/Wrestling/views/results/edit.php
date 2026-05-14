<?php
use App\Helpers\View;
$r = $result;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-pencil-square text-primary me-2"></i>
        Edytuj wynik — Zapasy
    </h3>
    <a href="<?= url('wrestling/results') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Anuluj
    </a>
</div>

<div class="card p-4">
    <form method="POST" action="<?= url('wrestling/results/' . (int)$r['id'] . '/update') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Zawodnik *</label>
                <select name="member_id" class="form-select" required>
                    <option value="">— wybierz —</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int)$m['id'] ?>" <?= (int)$r['member_id'] === (int)$m['id'] ? 'selected' : '' ?>>
                            <?= View::e($m['first_name'] . ' ' . $m['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nazwa zawodów *</label>
                <input type="text" name="competition_name" class="form-control"
                       value="<?= View::e($r['competition_name']) ?>" required maxlength="200">
            </div>
            <div class="col-md-4">
                <label class="form-label">Data *</label>
                <input type="date" name="competition_date" class="form-control"
                       value="<?= View::e($r['competition_date']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Styl *</label>
                <select name="style" class="form-select" required>
                    <?php foreach ($styles as $key => $label): ?>
                        <option value="<?= View::e($key) ?>" <?= $r['style'] === $key ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Kategoria wagowa</label>
                <select name="weight_class" class="form-select">
                    <option value="">— open —</option>
                    <optgroup label="Freestyle — Mężczyźni">
                        <?php foreach ($weightClassesMen as $wc): ?>
                            <option value="<?= $wc ?>" <?= $r['weight_class'] === $wc ? 'selected' : '' ?>>
                                <?= $wc ?> kg
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Klasyczny">
                        <?php foreach ($weightClassesGreco as $wc): ?>
                            <option value="<?= $wc ?>" <?= $r['weight_class'] === $wc ? 'selected' : '' ?>>
                                <?= $wc ?> kg (Greco)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Kobiety">
                        <?php foreach ($weightClassesWomen as $wc): ?>
                            <option value="<?= $wc ?>" <?= $r['weight_class'] === $wc ? 'selected' : '' ?>>
                                <?= $wc ?> kg (Kobiety)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Kategoria wiekowa</label>
                <input type="text" name="age_category" class="form-control"
                       value="<?= View::e($r['age_category'] ?? '') ?>"
                       placeholder="np. U17, Junior, Senior" maxlength="50">
            </div>
            <div class="col-md-6">
                <label class="form-label">Miejsce</label>
                <input type="number" name="placement" class="form-control"
                       value="<?= View::e($r['placement'] ?? '') ?>" min="1" max="99">
            </div>
            <div class="col-12">
                <label class="form-label">Notatki</label>
                <textarea name="notes" class="form-control" rows="2"><?= View::e($r['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= url('wrestling/results') ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i> Zapisz zmiany
            </button>
        </div>
    </form>
</div>
