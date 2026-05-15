<?php
use App\Helpers\View;

/** @var array<string, mixed>|null $achievement */
/** @var array<int, string> $categories */
/** @var array<int, string> $rarities */
/** @var array<string, string> $criteriaTypes */

$isEdit = $achievement !== null;
$action = $isEdit
    ? url('club/achievements/' . (int)$achievement['id'] . '/update')
    : url('club/achievements/store');

// Decode current criteria (jesli edycja).
$currentCriteria = [];
if ($isEdit && !empty($achievement['criteria'])) {
    $decoded = json_decode((string)$achievement['criteria'], true);
    if (is_array($decoded)) $currentCriteria = $decoded;
}
$currentType = (string)($currentCriteria['type'] ?? '');
?>
<h2 class="mb-3">
    <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> me-2"></i>
    <?= $isEdit ? 'Edycja odznaki' : 'Nowa odznaka klubu' ?>
</h2>

<form method="POST" action="<?= View::e($action) ?>" class="card p-4">
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Code (unikalny, bez spacji) <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control" required maxlength="60"
                   value="<?= View::e($achievement['code'] ?? '') ?>"
                   pattern="[a-z0-9_]+" placeholder="np. mvp_2025">
            <div class="form-text">Tylko male litery, cyfry i podkreslenia.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Nazwa <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required maxlength="120"
                   value="<?= View::e($achievement['name'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Opis</label>
            <textarea name="description" class="form-control" rows="2" maxlength="500"><?= View::e($achievement['description'] ?? '') ?></textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label">Ikona (emoji lub bi-class)</label>
            <input type="text" name="icon" class="form-control" maxlength="80"
                   value="<?= View::e($achievement['icon'] ?? '🏆') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Kategoria</label>
            <select name="category" class="form-select">
                <?php foreach ($categories as $c): ?>
                    <option value="<?= View::e($c) ?>" <?= ($achievement['category'] ?? '') === $c ? 'selected' : '' ?>>
                        <?= View::e($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Rzadkosc</label>
            <select name="rarity" class="form-select">
                <?php foreach ($rarities as $r): ?>
                    <option value="<?= View::e($r) ?>" <?= ($achievement['rarity'] ?? '') === $r ? 'selected' : '' ?>>
                        <?= View::e($r) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Punkty</label>
            <input type="number" name="points" class="form-control" min="0" max="10000"
                   value="<?= (int)($achievement['points'] ?? 10) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Sport key (opcjonalnie)</label>
            <input type="text" name="sport_key" class="form-control" maxlength="40"
                   value="<?= View::e($achievement['sport_key'] ?? '') ?>" placeholder="np. bjj">
        </div>
        <div class="col-md-4">
            <label class="form-label">Sort order</label>
            <input type="number" name="sort_order" class="form-control" min="0" max="65000"
                   value="<?= (int)($achievement['sort_order'] ?? 1000) ?>">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                       <?= (!$isEdit || (int)($achievement['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
                <label for="is_active" class="form-check-label">Aktywna</label>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <h5 class="mb-3"><i class="bi bi-gear me-2"></i>Kryteria zdobycia</h5>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Typ kryterium <span class="text-danger">*</span></label>
            <select name="criteria_type" id="criteria_type" class="form-select" required>
                <option value="">— wybierz —</option>
                <?php foreach ($criteriaTypes as $key => $label): ?>
                    <option value="<?= View::e($key) ?>" <?= $currentType === $key ? 'selected' : '' ?>>
                        <?= View::e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3" id="field_count">
            <label class="form-label">Wartosc / liczba</label>
            <input type="number" name="criteria_count" class="form-control" min="1" max="100000"
                   value="<?= (int)($currentCriteria['count'] ?? 0) ?>">
            <small class="text-muted">Uzywane dla: trainings_count, tournaments_played_count, season_wins, training_streak, referrals_count, belt_promotions_count</small>
        </div>
        <div class="col-md-3" id="field_place">
            <label class="form-label">Miejsce</label>
            <input type="number" name="criteria_place" class="form-control" min="1" max="999"
                   value="<?= (int)($currentCriteria['place'] ?? 0) ?>">
            <small class="text-muted">Tylko dla: tournament_place</small>
        </div>
        <div class="col-md-3" id="field_n">
            <label class="form-label">N (top)</label>
            <input type="number" name="criteria_n" class="form-control" min="1" max="999"
                   value="<?= (int)($currentCriteria['n'] ?? 0) ?>">
            <small class="text-muted">Tylko dla: tournament_top</small>
        </div>
        <div class="col-md-3" id="field_years">
            <label class="form-label">Lata</label>
            <input type="number" name="criteria_years" class="form-control" min="1" max="99"
                   value="<?= (int)($currentCriteria['years'] ?? 0) ?>">
            <small class="text-muted">Tylko dla: membership_years</small>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="<?= url('club/achievements') ?>" class="btn btn-link">Anuluj</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> <?= $isEdit ? 'Zapisz zmiany' : 'Utworz odznake' ?>
        </button>
    </div>
</form>

<script>
(function() {
    var typeSelect = document.getElementById('criteria_type');
    var fields = {
        count:  ['trainings_count','tournaments_played_count','season_wins','training_streak','referrals_count','belt_promotions_count'],
        place:  ['tournament_place'],
        n:      ['tournament_top'],
        years:  ['membership_years']
    };
    function show(id, on) {
        var el = document.getElementById('field_' + id);
        if (el) el.style.display = on ? '' : 'none';
    }
    function refresh() {
        var t = typeSelect.value;
        Object.keys(fields).forEach(function(k) {
            show(k, fields[k].indexOf(t) >= 0);
        });
    }
    typeSelect.addEventListener('change', refresh);
    refresh();
})();
</script>
