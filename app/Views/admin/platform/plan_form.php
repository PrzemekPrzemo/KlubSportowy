<?php use App\Helpers\View;
$isEdit = !empty($plan);
$action = $isEdit ? url('admin/platform/plans/' . (int)$plan['id'] . '/update') : url('admin/platform/plans/store');
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nazwa *</label>
            <input type="text" name="name" value="<?= View::e($plan['name'] ?? '') ?>" class="form-control" required></div>
        <div class="col-md-3"><label class="form-label">Cena miesięczna (PLN)</label>
            <input type="number" step="0.01" name="price_monthly" value="<?= View::e($plan['price_monthly'] ?? '0') ?>" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Cena roczna (PLN)</label>
            <input type="number" step="0.01" name="price_yearly" value="<?= View::e($plan['price_yearly'] ?? '0') ?>" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Max członków</label>
            <input type="number" name="max_members" value="<?= View::e($plan['max_members'] ?? '') ?>" class="form-control" placeholder="puste = bez limitu"></div>
        <div class="col-md-3"><label class="form-label">Max sportów</label>
            <input type="number" name="max_sports" value="<?= View::e($plan['max_sports'] ?? '') ?>" class="form-control" placeholder="puste = bez limitu"></div>
        <div class="col-md-3"><label class="form-label">Kolejność</label>
            <input type="number" name="sort_order" value="<?= View::e($plan['sort_order'] ?? '0') ?>" class="form-control"></div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" <?= ($plan['is_active'] ?? 1) ? 'checked' : '' ?>>
            <label for="active" class="form-check-label">Aktywny</label></div>
        </div>
        <div class="col-12"><label class="form-label">Features (JSON)</label>
            <textarea name="features" class="form-control" rows="3" style="font-family:monospace"><?= View::e($plan['features'] ?? '{"sms":true,"api":true,"backup":true}') ?></textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> ZAPISZ</button>
        <a href="<?= url('admin/platform/plans') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
