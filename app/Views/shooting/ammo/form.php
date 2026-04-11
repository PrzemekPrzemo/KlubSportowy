<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('shooting/ammo/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Kaliber *</label>
            <input type="text" name="caliber" class="form-control" required placeholder="np. 9x19, .22 LR">
        </div>
        <div class="col-md-4">
            <label class="form-label">Typ</label>
            <input type="text" name="type" class="form-control" placeholder="FMJ, HP, wadcutter">
        </div>
        <div class="col-md-4">
            <label class="form-label">Marka</label>
            <input type="text" name="brand" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Ilość startowa</label>
            <input type="number" name="quantity" value="0" min="0" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Cena jedn.</label>
            <input type="number" name="unit_price" step="0.01" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Min. stan (alert)</label>
            <input type="number" name="min_stock" min="0" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Notatki</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('shooting/ammo') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
