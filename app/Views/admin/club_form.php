<?php
use App\Helpers\View;
$action = $club
    ? url('admin/clubs/' . (int)$club['id'] . '/edit')
    : url('admin/clubs/create');
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Nazwa klubu *</label>
            <input type="text" name="name" value="<?= View::e($club['name'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Skrót</label>
            <input type="text" name="short_name" value="<?= View::e($club['short_name'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Miasto</label>
            <input type="text" name="city" value="<?= View::e($club['city'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">NIP</label>
            <input type="text" name="nip" value="<?= View::e($club['nip'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" name="is_active" value="1" id="active" class="form-check-input"
                       <?= ($club['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="active">Aktywny</label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" value="<?= View::e($club['email'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Telefon</label>
            <input type="text" name="phone" value="<?= View::e($club['phone'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Adres</label>
            <textarea name="address" class="form-control" rows="2"><?= View::e($club['address'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
