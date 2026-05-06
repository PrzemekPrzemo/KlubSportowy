<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-image text-primary me-2"></i>
        Logo systemu (Master Admin)
    </h3>
    <a href="<?= url('admin/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wróć
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    Logo wgrane tutaj zastępuje wbudowane <code>logo-cd.svg</code> /
    <code>logo-cd-white.svg</code>. Klub może dodatkowo nadpisać własnym
    logo per klub w <em>Branding klubu</em>. Dla raportów PDF używane są
    obie warstwy: logo systemu + logo klubu.
</div>

<form method="POST" action="<?= url('admin/platform/system-branding/save') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Color variant -->
        <div class="col-md-6">
            <div class="card p-3 h-100">
                <h5 class="mb-3"><i class="bi bi-palette"></i> Wariant kolorowy</h5>
                <small class="text-muted mb-2">Używany na jasnym tle: panel logowania, onboarding, dokumenty.</small>

                <div class="bg-light p-3 rounded mb-3 text-center">
                    <img src="<?= !empty($logoColor) ? url($logoColor) : '/images/logo-cd.svg' ?>"
                         alt="logo color"
                         style="max-width:100%; max-height:80px;">
                </div>

                <input type="file" name="logo_color" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
                <small class="text-muted mt-1">PNG / JPG / WebP / SVG, max 2 MB</small>

                <?php if ($logoColor): ?>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="reset_color" id="reset_color" class="form-check-input">
                        <label for="reset_color" class="form-check-label small">
                            Przywróć wbudowane logo (color)
                        </label>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- White variant -->
        <div class="col-md-6">
            <div class="card p-3 h-100">
                <h5 class="mb-3"><i class="bi bi-palette-fill"></i> Wariant biały</h5>
                <small class="text-muted mb-2">Używany na ciemnym tle: sidebar, landing, navbar portalu.</small>

                <div class="bg-dark p-3 rounded mb-3 text-center">
                    <img src="<?= !empty($logoWhite) ? url($logoWhite) : '/images/logo-cd-white.svg' ?>"
                         alt="logo white"
                         style="max-width:100%; max-height:80px;">
                </div>

                <input type="file" name="logo_white" class="form-control" accept=".png,.jpg,.jpeg,.webp,.svg">
                <small class="text-muted mt-1">PNG / JPG / WebP / SVG, max 2 MB</small>

                <?php if ($logoWhite): ?>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="reset_white" id="reset_white" class="form-check-input">
                        <label for="reset_white" class="form-check-label small">
                            Przywróć wbudowane logo (white)
                        </label>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Tekst alternatywny (alt)</label>
            <input type="text" name="logo_alt" class="form-control" maxlength="120"
                   value="<?= View::e($logoAlt ?? 'ClubDesk') ?>"
                   placeholder="np. ClubDesk — system zarządzania klubem">
            <small class="text-muted">Pojawi się w atrybucie <code>alt</code> obrazów.</small>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Zapisz zmiany
        </button>
    </div>
</form>
