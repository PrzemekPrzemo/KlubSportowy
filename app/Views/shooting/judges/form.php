<?php
use App\Helpers\View;
$isEdit = !empty($judge);
$action = $isEdit ? url('shooting/judges/' . (int)$judge['id'] . '/update') : url('shooting/judges/store');
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Sędzia *</label>
            <select name="member_id" class="form-select" required>
                <option value="">— wybierz —</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= (int)($judge['member_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                        <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Klasa PZSS</label>
            <select name="class" class="form-select">
                <?php foreach (['III','II','I','P'] as $c): ?>
                    <option value="<?= $c ?>" <?= ($judge['class'] ?? 'III') === $c ? 'selected' : '' ?>>Klasa <?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach (['aktywna','wygasla','zawieszona'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($judge['status'] ?? 'aktywna') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Numer licencji *</label>
            <input type="text" name="license_number" value="<?= View::e($judge['license_number'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Dyscypliny (CSV)</label>
            <input type="text" name="disciplines" value="<?= View::e($judge['disciplines'] ?? '') ?>" class="form-control" placeholder="PS, KS, TR">
        </div>
        <div class="col-md-3">
            <label class="form-label">Data wydania *</label>
            <input type="date" name="issue_date" value="<?= View::e($judge['issue_date'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Ważna do *</label>
            <input type="date" name="valid_until" value="<?= View::e($judge['valid_until'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Opłata wniesiona</label>
            <input type="number" step="0.01" name="fee_paid" value="<?= View::e($judge['fee_paid'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Notatki</label>
            <textarea name="notes" class="form-control" rows="3"><?= View::e($judge['notes'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('shooting/judges') ?>" class="btn btn-outline-secondary">Anuluj</a>
        <?php if ($isEdit): ?>
            <form method="POST" action="<?= url('shooting/judges/' . (int)$judge['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="ms-auto m-0">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Usuń</button>
            </form>
        <?php endif; ?>
    </div>
</form>
