<?php
use App\Helpers\View;
$isEdit = !empty($weapon);
$action = $isEdit ? url('shooting/weapons/' . (int)$weapon['id'] . '/update') : url('shooting/weapons/store');
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Kategoria</label>
            <select name="category" class="form-select">
                <?php foreach (['pistolet','karabin','strzelba','pneumatyczna','inna'] as $c): ?>
                    <option value="<?= $c ?>" <?= ($weapon['category'] ?? 'pistolet') === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Marka</label>
            <input type="text" name="brand" value="<?= View::e($weapon['brand'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Model</label>
            <input type="text" name="model" value="<?= View::e($weapon['model'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Kaliber</label>
            <input type="text" name="caliber" value="<?= View::e($weapon['caliber'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Numer seryjny *</label>
            <input type="text" name="serial_number" value="<?= View::e($weapon['serial_number'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Rok produkcji</label>
            <input type="number" name="production_year" value="<?= View::e($weapon['production_year'] ?? '') ?>" min="1900" max="2099" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Stan techniczny</label>
            <select name="condition_state" class="form-select">
                <?php foreach (['nowa','dobra','uzytkowa','do_serwisu','wycofana'] as $c): ?>
                    <option value="<?= $c ?>" <?= ($weapon['condition_state'] ?? 'dobra') === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Data zakupu</label>
            <input type="date" name="purchase_date" value="<?= View::e($weapon['purchase_date'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Cena zakupu</label>
            <input type="number" step="0.01" name="purchase_price" value="<?= View::e($weapon['purchase_price'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Notatki</label>
            <textarea name="notes" class="form-control" rows="3"><?= View::e($weapon['notes'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('shooting/weapons') ?>" class="btn btn-outline-secondary">Anuluj</a>
        <?php if ($isEdit): ?>
            <form method="POST" action="<?= url('shooting/weapons/' . (int)$weapon['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć broń?')" class="ms-auto m-0">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Usuń</button>
            </form>
        <?php endif; ?>
    </div>
</form>
