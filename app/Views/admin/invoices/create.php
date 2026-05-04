<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-receipt me-2"></i>Nowa faktura</h4>
    <a href="<?= url('admin/invoices') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Powrót</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= url('admin/invoices/store') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-6">
                <label class="form-label">Klub <span class="text-danger">*</span></label>
                <select name="club_id" class="form-select" required>
                    <option value="">— Wybierz klub —</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= View::e($c['name']) ?> <?= !empty($c['nip']) ? ' (NIP: ' . View::e($c['nip']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Numer faktury <span class="text-danger">*</span></label>
                <input type="text" name="number" value="<?= View::e($nextNumber) ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Data wystawienia <span class="text-danger">*</span></label>
                <input type="date" name="issue_date" value="<?= View::e($today) ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Termin płatności <span class="text-danger">*</span></label>
                <input type="date" name="due_date" value="<?= View::e($defaultDue) ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Kwota (PLN) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" name="total" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status startowy</label>
                <select name="status" class="form-select">
                    <option value="draft">Szkic (draft)</option>
                    <option value="issued" selected>Wystawiona (issued)</option>
                    <option value="paid">Zapłacona (paid)</option>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Notatki</label>
                <textarea name="notes" rows="3" class="form-control" placeholder="Opis, pozycje, odniesienie do subskrypcji..."></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="<?= url('admin/invoices') ?>" class="btn btn-outline-secondary">Anuluj</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Utwórz fakturę</button>
            </div>
        </form>
    </div>
</div>
