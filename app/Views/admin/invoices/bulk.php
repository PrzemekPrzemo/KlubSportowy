<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i> Masowe generowanie faktur</h3>
    <a href="<?= url('admin/invoices') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card p-4">
    <form method="POST" action="<?= url('admin/invoices/bulk-generate') ?>">
        <?= csrf_field() ?>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Miesiąc *</label>
                <input type="month" name="month" class="form-control" value="<?= View::e($defaultMonth ?? date('Y-m')) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Kwota domyślna (PLN)</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="99.00">
            </div>
        </div>

        <h5>Wybór klubów (puste = wszystkie)</h5>
        <div class="row g-2 mb-4" style="max-height:400px; overflow-y:auto;">
            <?php foreach ($clubs as $c): ?>
                <div class="col-md-4">
                    <label class="form-check">
                        <input type="checkbox" name="club_ids[]" value="<?= (int)$c['id'] ?>" class="form-check-input">
                        <span class="form-check-label"><?= View::e($c['name']) ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            System jest idempotentny — pomija kluby, dla których faktura w wybranym miesiącu już istnieje.
            Numeracja: <code>FV/{rok}/{miesiac}/{NNNN}</code>.
        </div>

        <div class="d-flex justify-content-end">
            <button class="btn btn-primary" onclick="return confirm('Wygenerować faktury masowo?')">
                <i class="bi bi-magic"></i> Generuj faktury
            </button>
        </div>
    </form>
</div>
