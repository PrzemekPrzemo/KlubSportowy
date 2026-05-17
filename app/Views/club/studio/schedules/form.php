<?php use App\Helpers\Csrf; use App\Helpers\View; ?>

<h4 class="mb-3">
    <i class="bi bi-calendar-plus text-primary me-2"></i>
    <?= View::e($title) ?> — <?= View::e($sportName) ?>
</h4>

<?php
$isEdit = !empty($row);
$action = $isEdit
    ? url('club/studio/' . $sport . '/schedules/' . (int)$row['id'] . '/update')
    : url('club/studio/' . $sport . '/schedules/store');
?>

<form action="<?= $action ?>" method="POST" class="card card-body shadow-sm">
    <?= Csrf::field() ?>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nazwa klasy</label>
            <input name="name" required maxlength="200" class="form-control"
                   value="<?= View::e($row['name'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Poziom</label>
            <select name="difficulty" class="form-select">
                <?php foreach ($difficulties as $d): ?>
                    <option value="<?= $d ?>" <?= (($row['difficulty'] ?? 'open') === $d) ? 'selected' : '' ?>>
                        <?= View::e(ucfirst($d)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Sala</label>
            <input name="room" maxlength="100" class="form-control"
                   value="<?= View::e($row['room'] ?? '') ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label">Dzień tygodnia</label>
            <select name="day_of_week" class="form-select">
                <?php foreach ($dayLabels as $k => $lab): ?>
                    <option value="<?= $k ?>" <?= ((int)($row['day_of_week'] ?? 1) === $k) ? 'selected' : '' ?>>
                        <?= View::e($lab) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Godzina startu</label>
            <input type="time" name="time_start" required class="form-control"
                   value="<?= View::e(substr((string)($row['time_start'] ?? '18:00'), 0, 5)) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Czas (min)</label>
            <input type="number" name="duration_min" min="15" max="240" class="form-control"
                   value="<?= (int)($row['duration_min'] ?? 60) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Limit miejsc</label>
            <input type="number" name="max_capacity" min="1" max="200" class="form-control"
                   value="<?= (int)($row['max_capacity'] ?? 15) ?>">
        </div>

        <div class="col-12">
            <label class="form-label">Opis</label>
            <textarea name="description" rows="3" class="form-control"><?= View::e($row['description'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="active" id="active" class="form-check-input" <?= ((int)($row['active'] ?? 1) === 1) ? 'checked' : '' ?>>
                <label for="active" class="form-check-label">Aktywna (widoczna w katalogu)</label>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Zapisz</button>
        <a href="<?= url('club/studio/' . $sport . '/schedules') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
