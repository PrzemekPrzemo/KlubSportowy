<?php use App\Helpers\View; ?>
<div class="alert alert-info">
    <strong>Edycja brandingu klubu:</strong> <?= View::e($club['name']) ?> (ID: <?= (int)$club['id'] ?>)
</div>
<form method="POST" action="<?= url('admin/platform/branding/' . (int)$club['id'] . '/save') ?>" enctype="multipart/form-data" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Kolor główny</label>
            <input type="color" name="primary_color" value="<?= View::e($custom['primary_color'] ?? '#EE2C28') ?>" class="form-control form-control-color"></div>
        <div class="col-md-4"><label class="form-label">Kolor sidebara</label>
            <input type="color" name="navbar_bg" value="<?= View::e($custom['navbar_bg'] ?? '#232322') ?>" class="form-control form-control-color"></div>
        <div class="col-md-4"><label class="form-label">Kolor akcentu</label>
            <input type="color" name="accent_color" value="<?= View::e($custom['accent_color'] ?? '#EE2C28') ?>" class="form-control form-control-color"></div>
        <div class="col-md-6"><label class="form-label">Motto</label>
            <input type="text" name="motto" value="<?= View::e($custom['motto'] ?? '') ?>" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Subdomena</label>
            <input type="text" name="subdomain" value="<?= View::e($custom['subdomain'] ?? '') ?>" class="form-control" placeholder="np. azs-warszawa"></div>
        <div class="col-md-6"><label class="form-label">Logo (upload)</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <?php if (!empty($custom['logo_path'])): ?>
                <small>Aktualne: <code><?= View::e($custom['logo_path']) ?></code></small>
            <?php endif; ?>
        </div>
        <div class="col-12"><label class="form-label">Custom CSS</label>
            <textarea name="custom_css" class="form-control" rows="4" style="font-family:monospace"><?= View::e($custom['custom_css'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-check2"></i> ZAPISZ BRANDING</button></div>
</form>
