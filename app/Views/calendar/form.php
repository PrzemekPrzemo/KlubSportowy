<?php
use App\Helpers\View;
$isEdit = !empty($event);
$action = $isEdit ? url('calendar/' . (int)$event['id'] . '/update') : url('calendar/store');
$startAt = '';
if (!empty($event['start_at'])) {
    $startAt = str_replace(' ', 'T', substr($event['start_at'], 0, 16));
}
$endAt = '';
if (!empty($event['end_at'])) {
    $endAt = str_replace(' ', 'T', substr($event['end_at'], 0, 16));
}
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Tytuł *</label>
            <input type="text" name="title" value="<?= View::e($event['title'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Kategoria</label>
            <select name="category_id" class="form-select">
                <option value="">—</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (int)($event['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= View::e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Sport</label>
            <select name="sport_id" class="form-select">
                <option value="">— ogólnoklubowe —</option>
                <?php foreach ($sports as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= (int)($event['sport_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= View::e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Start *</label>
            <input type="datetime-local" name="start_at" value="<?= View::e($startAt) ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Koniec</label>
            <input type="datetime-local" name="end_at" value="<?= View::e($endAt) ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Miejsce</label>
            <input type="text" name="location" value="<?= View::e($event['location'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Widoczność</label>
            <select name="visibility" class="form-select">
                <?php foreach (['club'=>'klub','private'=>'prywatne','public'=>'publiczne'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= ($event['visibility'] ?? 'club') === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" name="all_day" value="1" id="allday" class="form-check-input" <?= !empty($event['all_day']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="allday">Cały dzień</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Opis</label>
            <textarea name="description" class="form-control" rows="3"><?= View::e($event['description'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('calendar') ?>" class="btn btn-outline-secondary">Anuluj</a>
        <?php if ($isEdit): ?>
            <form method="POST" action="<?= url('calendar/' . (int)$event['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="ms-auto m-0">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Usuń</button>
            </form>
        <?php endif; ?>
    </div>
</form>
