<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('football/transfers/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Zawodnik *</label>
            <select name="member_id" class="form-select" required>
                <option value="">—</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-3"><label class="form-label">Kierunek</label>
            <select name="direction" class="form-select">
                <option value="przychodzacy">przychodzący</option>
                <option value="odchodzacy">odchodzący</option>
                <option value="wypozyczenie">wypożyczenie</option>
            </select></div>
        <div class="col-md-3"><label class="form-label">Data *</label>
            <input type="date" name="transfer_date" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Z klubu</label>
            <input type="text" name="from_club" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Do klubu</label>
            <input type="text" name="to_club" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Kwota transferu</label>
            <input type="number" step="0.01" name="fee" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Kontrakt do</label>
            <input type="date" name="contract_until" class="form-control"></div>
        <div class="col-12"><label class="form-label">Notatki</label>
            <textarea name="notes" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('football/transfers') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
