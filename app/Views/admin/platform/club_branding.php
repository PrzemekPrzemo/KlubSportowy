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
    </div>

    <hr class="my-4">
    <h5 class="mb-3"><i class="bi bi-images"></i> Logotypy klubu (3 sloty)</h5>
    <small class="text-muted d-block mb-3">
        Logo używane w sidebarze klubu i na dokumentach (PDF, raporty).
        Wgraj 1-3 warianty: <strong>main</strong> (główne — domyślne wszędzie),
        <strong>alt</strong> (alternatywne — np. uproszczone), <strong>dark</strong>
        (na ciemnym tle — jasny kolor).
    </small>

    <div class="row g-3">
        <?php foreach (['main' => 'Główne (logo_path)', 'alt' => 'Alternatywne', 'dark' => 'Ciemne tło'] as $variant => $label):
            $field = $variant === 'main' ? 'logo_path' : "logo_{$variant}_path";
            $name  = $variant === 'main' ? 'logo' : "logo_{$variant}";
        ?>
            <div class="col-md-4">
                <label class="form-label"><?= View::e($label) ?></label>
                <?php if (!empty($custom[$field])): ?>
                    <div class="bg-light p-2 rounded mb-2 text-center">
                        <img src="<?= url($custom[$field]) ?>" style="max-height:60px; max-width:100%;">
                    </div>
                <?php endif; ?>
                <input type="file" name="<?= $name ?>" class="form-control form-control-sm" accept="image/*">
                <?php if (!empty($custom[$field])): ?>
                    <div class="form-check mt-1">
                        <input type="checkbox" name="reset_<?= $variant ?>" id="reset_<?= $variant ?>" class="form-check-input">
                        <label for="reset_<?= $variant ?>" class="form-check-label small">Usuń</label>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-12"><label class="form-label">Custom CSS</label>
            <textarea name="custom_css" class="form-control" rows="4" style="font-family:monospace"><?= View::e($custom['custom_css'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-check2"></i> ZAPISZ BRANDING</button></div>
</form>
