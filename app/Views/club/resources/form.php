<?php use App\Helpers\View; ?>
<?php
$r = $resource ?? null;
$isEdit = $r !== null;
$action = $isEdit ? url('club/resources/' . (int)$r['id'] . '/update') : url('club/resources/store');
$weekdaysCurrent = $r ? array_map('intval', explode(',', $r['available_weekdays'] ?? '1,2,3,4,5,6,7')) : [1,2,3,4,5,6,7];
$weekdayLabels = [1=>'Pon',2=>'Wt',3=>'Śr',4=>'Czw',5=>'Pt',6=>'Sob',7=>'Ndz'];
?>

<h4 class="mb-3"><i class="bi bi-box-seam"></i> <?= $isEdit ? 'Edytuj zasób' : 'Nowy zasób' ?></h4>

<form method="POST" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Nazwa *</label>
            <input name="name" class="form-control" required value="<?= View::e($r['name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Typ</label>
            <select name="type" class="form-select">
                <?php foreach (['room'=>'Sala','court'=>'Kort','equipment'=>'Sprzęt','field'=>'Boisko','pool_lane'=>'Tor basenowy','other'=>'Inne'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= ($r['type'] ?? 'room') === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Pojemność</label>
            <input type="number" min="0" name="capacity" class="form-control" value="<?= View::e($r['capacity'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Lokalizacja</label>
            <input name="location" class="form-control" value="<?= View::e($r['location'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Kolor</label>
            <input type="color" name="color" class="form-control form-control-color" value="<?= View::e($r['color'] ?? '#6c757d') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Aktywny</label>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= ($r === null || (int)($r['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Opis</label>
            <textarea name="description" class="form-control" rows="2"><?= View::e($r['description'] ?? '') ?></textarea>
        </div>

        <div class="col-md-3">
            <label class="form-label">Slot (min)</label>
            <select name="booking_unit_minutes" class="form-select">
                <?php foreach ([15,30,45,60,90,120,180] as $u): ?>
                    <option value="<?= $u ?>" <?= (int)($r['booking_unit_minutes'] ?? 60) === $u ? 'selected' : '' ?>><?= $u ?> min</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Min. wyprzedzenie (godz)</label>
            <input type="number" min="0" name="min_advance_hours" class="form-control" value="<?= (int)($r['min_advance_hours'] ?? 0) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Max wyprzedzenie (dni)</label>
            <input type="number" min="1" name="max_advance_days" class="form-control" value="<?= (int)($r['max_advance_days'] ?? 30) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Max długość (min)</label>
            <input type="number" min="15" name="max_duration_minutes" class="form-control" value="<?= View::e($r['max_duration_minutes'] ?? '') ?>" placeholder="bez limitu">
        </div>

        <div class="col-md-3">
            <label class="form-label">Otwarte od</label>
            <input type="time" name="available_from" class="form-control" value="<?= View::e($r['available_from'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Otwarte do</label>
            <input type="time" name="available_until" class="form-control" value="<?= View::e($r['available_until'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label d-block">Dni tygodnia</label>
            <?php foreach ($weekdayLabels as $num=>$lbl): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="available_weekdays[]" value="<?= $num ?>"
                           id="wd_<?= $num ?>" <?= in_array($num, $weekdaysCurrent, true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="wd_<?= $num ?>"><?= $lbl ?></label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="col-md-6">
            <label class="form-label">Powiązanie ze sportem (klucz, opcjonalne)</label>
            <input name="sport_key" class="form-control" value="<?= View::e($r['sport_key'] ?? '') ?>" placeholder="np. tennis, swimming">
        </div>
        <div class="col-md-6">
            <label class="form-label">Ikona Bootstrap (np. bi-water)</label>
            <input name="icon" class="form-control" value="<?= View::e($r['icon'] ?? '') ?>">
        </div>

        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="requires_approval" value="1" id="ra" <?= !empty($r['requires_approval']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="ra">Rezerwacje wymagają akceptacji admina (status pending)</label>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-save"></i> Zapisz</button>
        <a href="<?= url('club/resources') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
