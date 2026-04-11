<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('club/customization/save') ?>" enctype="multipart/form-data" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Motto / hasło klubu</label>
            <input type="text" name="motto" value="<?= View::e($custom['motto'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Subdomena</label>
            <input type="text" name="subdomain" value="<?= View::e($custom['subdomain'] ?? '') ?>" class="form-control" placeholder="np. azs-warszawa">
            <small class="text-muted">np. <code>azs-warszawa.klubsportowy.pl</code></small>
        </div>
        <div class="col-md-12">
            <label class="form-label">Logo klubu (PNG, JPG, WebP, SVG)</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <?php if (!empty($custom['logo_path'])): ?>
                <small class="text-muted d-block mt-2">
                    Aktualne: <code><?= View::e($custom['logo_path']) ?></code>
                </small>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label">Kolor główny</label>
            <input type="color" name="primary_color" value="<?= View::e($custom['primary_color'] ?? '#0d6efd') ?>" class="form-control form-control-color">
        </div>
        <div class="col-md-4">
            <label class="form-label">Kolor sidebara</label>
            <input type="color" name="navbar_bg" value="<?= View::e($custom['navbar_bg'] ?? '#212529') ?>" class="form-control form-control-color">
        </div>
        <div class="col-md-4">
            <label class="form-label">Kolor akcentu</label>
            <input type="color" name="accent_color" value="<?= View::e($custom['accent_color'] ?? '#198754') ?>" class="form-control form-control-color">
        </div>
        <div class="col-12">
            <label class="form-label">Dodatkowe CSS (zaawansowane)</label>
            <textarea name="custom_css" rows="5" class="form-control" style="font-family: monospace;"><?= View::e($custom['custom_css'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
    </div>
</form>
