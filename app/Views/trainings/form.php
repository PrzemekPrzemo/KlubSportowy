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

// Lista trenerow klubu (do selecta instructor_id + AJAX conflict check)
$trainersList = [];
try {
    $stmt = \App\Helpers\Database::pdo()->prepare(
        "SELECT u.id, u.full_name, u.username FROM users u
         JOIN user_clubs uc ON uc.user_id = u.id
         WHERE uc.club_id = ? AND uc.role IN ('trener','instruktor') AND uc.is_active = 1
         ORDER BY u.full_name"
    );
    $stmt->execute([\App\Helpers\ClubContext::current()]);
    $trainersList = $stmt->fetchAll();
} catch (\Throwable) { /* ignore */ }
$selectedInstructorId = (int)($training['instructor_id'] ?? 0);
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
        <div class="col-md-5">
            <label class="form-label"><?= __('form.location') ?></label>
            <input type="text" name="location" value="<?= View::e($training['location'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Trener prowadzacy</label>
            <select name="instructor_id" id="trainerSelect" class="form-select">
                <option value="">— brak —</option>
                <?php foreach ($trainersList as $tr): ?>
                    <option value="<?= (int)$tr['id'] ?>" <?= $selectedInstructorId === (int)$tr['id'] ? 'selected' : '' ?>>
                        <?= View::e($tr['full_name'] ?? $tr['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.max_participants') ?></label>
            <input type="number" min="1" name="max_participants" value="<?= View::e($training['max_participants'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label"><?= __('form.description') ?></label>
            <textarea name="description" class="form-control" rows="3"><?= View::e($training['description'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <div id="trainerConflictBanner" class="alert alert-warning d-none" role="alert">
                <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Wykryte konflikty dostepnosci trenera</h6>
                <ul id="trainerConflictList" class="mb-2"></ul>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="force_save" id="forceSaveChk" value="1">
                    <label class="form-check-label" for="forceSaveChk">
                        <strong>Tak, zapisz mimo konfliktow.</strong> Zostana zapisane do audytu konfliktow.
                    </label>
                </div>
            </div>
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

// ── Trainer conflict detection (AJAX) ──────────────────────────
(function() {
    var trainerSel = document.getElementById('trainerSelect');
    var startInp   = document.querySelector('[name=start_time]');
    var endInp     = document.querySelector('[name=end_time]');
    var banner     = document.getElementById('trainerConflictBanner');
    var listEl     = document.getElementById('trainerConflictList');
    var csrf       = '<?= View::e(csrf_token()) ?>';
    var trainingId = <?= $isEdit ? (int)$training['id'] : 0 ?>;
    var timer = null;

    function maybeCheck() {
        if (timer) clearTimeout(timer);
        timer = setTimeout(doCheck, 350);
    }

    function doCheck() {
        var tid = parseInt(trainerSel.value || 0, 10);
        var st  = startInp.value;
        if (!tid || !st) { banner.classList.add('d-none'); return; }

        var fd = new FormData();
        fd.append('_csrf', csrf);
        fd.append('trainer_id', String(tid));
        fd.append('starts_at', st);
        if (endInp.value) fd.append('ends_at', endInp.value);
        if (trainingId)   fd.append('training_id', String(trainingId));

        fetch('<?= url('club/trainings/check-conflicts') ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(j) {
                if (!j.ok || !j.conflicts || j.conflicts.length === 0) {
                    banner.classList.add('d-none');
                    listEl.innerHTML = '';
                    return;
                }
                listEl.innerHTML = '';
                j.conflicts.forEach(function(c) {
                    var li = document.createElement('li');
                    li.innerHTML = '<strong>' + c.type + '</strong>: ' + (c.details || '');
                    listEl.appendChild(li);
                });
                banner.classList.remove('d-none');
            })
            .catch(function() { /* ignore */ });
    }

    if (trainerSel) trainerSel.addEventListener('change', maybeCheck);
    if (startInp)   startInp.addEventListener('change', maybeCheck);
    if (endInp)     endInp.addEventListener('change', maybeCheck);
    // Initial check (edit mode)
    if (trainerSel && trainerSel.value && startInp && startInp.value) maybeCheck();
})();
</script>
