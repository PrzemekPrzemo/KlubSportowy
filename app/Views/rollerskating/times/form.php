<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('rollerskating/times/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Zawodnik *</label>
            <select name="member_id" class="form-select" required>
                <option value="">—</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-3"><label class="form-label">Dystans</label>
            <input type="text" name="distance" class="form-control" placeholder="np. 500m, 1000m"></div>
        <div class="col-md-3"><label class="form-label">Data *</label>
            <input type="date" name="record_date" value="<?= date('Y-m-d') ?>" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Czas * <small class="text-muted">(format: m:ss.mmm lub ss.mmm)</small></label>
            <input type="text" name="time_raw" class="form-control" required placeholder="1:23.456"></div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check"><input type="checkbox" name="is_personal_best" value="1" class="form-check-input" id="pb">
            <label for="pb" class="form-check-label">Rekord życiowy (PB)</label></div>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('rollerskating/times') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
