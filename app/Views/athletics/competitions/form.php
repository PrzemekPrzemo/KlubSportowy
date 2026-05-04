<?php
use App\Helpers\View;
$isEdit = !empty($competition);
$action = $isEdit
    ? url('athletics/competitions/' . (int)$competition['id'] . '/update')
    : url('athletics/competitions/store');
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Nazwa zawodów *</label>
            <input type="text" name="name" class="form-control" required
                   value="<?= View::e($competition['name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Typ</label>
            <select name="type" class="form-select">
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t ?>" <?= ($competition['type'] ?? '') === $t ? 'selected' : '' ?>>
                        <?= str_replace('_', ' ', $t) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Data rozpoczęcia *</label>
            <input type="date" name="date_from" class="form-control" required
                   value="<?= View::e($competition['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Data zakończenia</label>
            <input type="date" name="date_to" class="form-control"
                   value="<?= View::e($competition['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= ($competition['status'] ?? 'zaplanowane') === $s ? 'selected' : '' ?>>
                        <?= str_replace('_', ' ', $s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Lokalizacja</label>
            <input type="text" name="location" class="form-control"
                   value="<?= View::e($competition['location'] ?? '') ?>">
        </div>

        <div class="col-12">
            <label class="form-label">Notatki</label>
            <textarea name="notes" class="form-control" rows="3"><?= View::e($competition['notes'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('athletics/competitions') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
