<?php
use App\Helpers\View;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="bi bi-airplane"></i> Dodaj urlop: <?= View::e($trainer['full_name'] ?? $trainer['username']) ?></h2>
    <a href="<?= url('club/trainer-schedule') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrot
    </a>
</div>

<form method="POST" action="<?= url('club/trainer-schedule/' . (int)$trainer['id'] . '/leaves/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Typ</label>
            <select name="leave_type" class="form-select" required>
                <option value="vacation">Urlop</option>
                <option value="sick">Chorobowe</option>
                <option value="training">Szkolenie</option>
                <option value="other">Inne</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Od *</label>
            <input type="date" name="date_from" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Do *</label>
            <input type="date" name="date_to" class="form-control" required>
        </div>
        <div class="col-12">
            <label class="form-label">Powod (opcjonalnie)</label>
            <textarea name="reason" class="form-control" rows="3" maxlength="500"></textarea>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz urlop</button>
        <a href="<?= url('club/trainer-schedule') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
