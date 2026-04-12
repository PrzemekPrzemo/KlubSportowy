<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('athletics/records/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Zawodnik *</label>
            <select name="member_id" class="form-select" required>
                <option value="">—</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-6"><label class="form-label">Dyscyplina</label>
            <select name="discipline_id" class="form-select">
                <option value="">—</option>
                <?php foreach ($disciplines as $d): ?>
                    <option value="<?= (int)$d['id'] ?>"><?= View::e($d['name']) ?> (<?= View::e($d['short_code']) ?>)</option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-3"><label class="form-label">Wynik *</label>
            <input type="number" step="0.001" name="result_value" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Jednostka</label>
            <select name="result_unit" class="form-select">
                <option value="s">sekundy (s)</option>
                <option value="min">minuty (min)</option>
                <option value="m">metry (m)</option>
                <option value="cm">centymetry (cm)</option>
                <option value="kg">kilogramy (kg)</option>
            </select></div>
        <div class="col-md-3"><label class="form-label">Data *</label>
            <input type="date" name="record_date" value="<?= date('Y-m-d') ?>" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Miejsce</label>
            <input type="text" name="location" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Nazwa zawodów</label>
            <input type="text" name="competition_name" class="form-control"></div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check"><input type="checkbox" name="is_personal_best" value="1" class="form-check-input" id="pb">
            <label for="pb" class="form-check-label">PB</label></div>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check"><input type="checkbox" name="is_club_record" value="1" class="form-check-input" id="cr">
            <label for="cr" class="form-check-label">Rekord klubu</label></div>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('athletics/records') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
