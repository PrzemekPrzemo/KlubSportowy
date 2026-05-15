<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-filetype-xml text-primary me-2"></i> Eksport JPK_FA</h3>
    <a href="<?= url('admin/invoices') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card p-4">
    <form method="POST" action="<?= url('admin/invoices/jpk-fa/export') ?>">
        <?= csrf_field() ?>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Data od *</label>
                <input type="date" name="from" class="form-control" value="<?= View::e($defaultFrom) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Data do *</label>
                <input type="date" name="to" class="form-control" value="<?= View::e($defaultTo) ?>" required>
            </div>
        </div>

        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Plik JPK_FA(4):</strong> uproszczony eksport zgodny ze strukturą Ministerstwa Finansów.
            Zawiera nagłówek, podmiot sprzedający (dane z platform_company_*), listę faktur ze statusem
            <code>issued</code> lub <code>paid</code> z wybranego zakresu oraz blok kontrolny.
            Wersja produkcyjna wymaga uzupełnienia danych identyfikacyjnych podmiotu w ustawieniach platformy.
        </div>

        <div class="d-flex justify-content-end">
            <button class="btn btn-primary">
                <i class="bi bi-download"></i> Pobierz JPK_FA XML
            </button>
        </div>
    </form>
</div>
