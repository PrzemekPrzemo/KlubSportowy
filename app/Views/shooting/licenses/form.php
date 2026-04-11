<?php
use App\Helpers\View;
$isEdit = !empty($license);
$action = $isEdit ? url('shooting/licenses/' . (int)$license['id'] . '/update') : url('shooting/licenses/store');
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Zawodnik *</label>
            <select name="member_id" class="form-select" required>
                <option value="">— wybierz —</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= (int)($license['member_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                        <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?> (#<?= View::e($m['member_number']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Typ licencji</label>
            <select name="license_type" class="form-select">
                <?php foreach (['zawodnicza','trenerska','sedziowska','klubowa','patent'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($license['license_type'] ?? 'zawodnicza') === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach (['aktywna','wygasla','zawieszona'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($license['status'] ?? 'aktywna') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Numer licencji *</label>
            <input type="text" name="license_number" value="<?= View::e($license['license_number'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Data wydania *</label>
            <input type="date" name="issue_date" value="<?= View::e($license['issue_date'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Ważna do *</label>
            <input type="date" name="valid_until" value="<?= View::e($license['valid_until'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-12">
            <label class="form-label">QR / URL PZSS</label>
            <input type="text" name="qr_code" value="<?= View::e($license['qr_code'] ?? '') ?>" class="form-control" placeholder="link do zweryfikowania w systemie PZSS">
        </div>
        <div class="col-12">
            <label class="form-label">Notatki</label>
            <textarea name="notes" class="form-control" rows="3"><?= View::e($license['notes'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('shooting/licenses') ?>" class="btn btn-outline-secondary">Anuluj</a>
        <?php if ($isEdit): ?>
            <form method="POST" action="<?= url('shooting/licenses/' . (int)$license['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="ms-auto m-0">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Usuń</button>
            </form>
        <?php endif; ?>
    </div>
</form>
