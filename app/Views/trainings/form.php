<?php
use App\Helpers\View;
$isEdit = !empty($training);
$action = $isEdit ? url('trainings/' . (int)$training['id'] . '/update') : url('trainings/store');
$startTime = '';
if (!empty($training['start_time'])) {
    $startTime = str_replace(' ', 'T', substr($training['start_time'], 0, 16));
}
$endTime = '';
if (!empty($training['end_time'])) {
    $endTime = str_replace(' ', 'T', substr($training['end_time'], 0, 16));
}
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label"><?= __('form.name') ?> *</label>
            <input type="text" name="name" value="<?= View::e($training['name'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.status') ?></label>
            <select name="status" class="form-select">
                <?php foreach (['zaplanowany','w_trakcie','zakonczony','odwolany'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($training['status'] ?? 'zaplanowany') === $s ? 'selected' : '' ?>><?= __('training.status_' . $s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= __('form.sports_section') ?></label>
            <select name="club_sport_id" class="form-select">
                <option value=""><?= __('form.club_wide_m') ?></option>
                <?php foreach ($sports as $cs): ?>
                    <option value="<?= (int)$cs['club_sport_id'] ?>"
                            data-sport="<?= (int)$cs['id'] ?>"
                            <?= (int)($training['club_sport_id'] ?? 0) === (int)$cs['club_sport_id'] ? 'selected' : '' ?>>
                        <?= View::e($cs['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="sport_id" id="sportIdHidden" value="<?= (int)($training['sport_id'] ?? 0) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.start_time') ?> *</label>
            <input type="datetime-local" name="start_time" value="<?= View::e($startTime) ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.end_time') ?></label>
            <input type="datetime-local" name="end_time" value="<?= View::e($endTime) ?>" class="form-control">
        </div>
        <div class="col-md-8">
            <label class="form-label"><?= __('form.location') ?></label>
            <input type="text" name="location" value="<?= View::e($training['location'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.max_participants') ?></label>
            <input type="number" min="1" name="max_participants" value="<?= View::e($training['max_participants'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label"><?= __('form.description') ?></label>
            <textarea name="description" class="form-control" rows="3"><?= View::e($training['description'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> <?= __('btn.save') ?></button>
        <a href="<?= url('trainings') ?>" class="btn btn-outline-secondary"><?= __('btn.cancel') ?></a>
    </div>
</form>
<script>
document.querySelector('[name=club_sport_id]').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    document.getElementById('sportIdHidden').value = opt.getAttribute('data-sport') || '';
});
</script>
