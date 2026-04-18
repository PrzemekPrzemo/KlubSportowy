<?php use App\Helpers\View; ?>

<div class="card">
    <div class="card-body p-4">
        <h4 class="mb-1"><i class="bi bi-palette"></i> Branding klubu</h4>
        <p class="text-muted mb-4">Dostosuj wygląd panelu do identyfikacji wizualnej klubu.</p>

        <form method="POST" action="<?= url('onboarding/step3') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="logo" class="form-label">Logo klubu</label>
                        <input type="file" class="form-control" id="logo" name="logo"
                               accept=".png,.jpg,.jpeg,.svg,.webp">
                        <div class="form-text">PNG, JPG, SVG lub WEBP. Max 2 MB.</div>
                        <?php if (!empty($branding['logo_path'])): ?>
                            <div class="mt-2">
                                <img src="<?= url($branding['logo_path']) ?>" alt="Logo"
                                     style="max-height:60px;" class="rounded">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="primary_color" class="form-label">Kolor podstawowy</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color" id="primary_color"
                                   name="primary_color" value="<?= View::e($branding['primary_color'] ?? '#0d6efd') ?>">
                            <input type="text" class="form-control" style="max-width:120px;"
                                   value="<?= View::e($branding['primary_color'] ?? '#0d6efd') ?>"
                                   id="primary_color_text" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="accent_color" class="form-label">Kolor akcentowy</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color" id="accent_color"
                                   name="accent_color" value="<?= View::e($branding['accent_color'] ?? '#198754') ?>">
                            <input type="text" class="form-control" style="max-width:120px;"
                                   value="<?= View::e($branding['accent_color'] ?? '#198754') ?>"
                                   id="accent_color_text" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="motto" class="form-label">Motto klubu</label>
                        <input type="text" class="form-control" id="motto" name="motto"
                               value="<?= View::e($branding['motto'] ?? '') ?>"
                               placeholder="np. Razem silniejsi!">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Podglad</label>
                    <div class="card border" id="brandingPreview">
                        <div class="card-header text-white" id="previewHeader"
                             style="background-color: <?= View::e($branding['primary_color'] ?? '#0d6efd') ?>">
                            <strong id="previewTitle">Twój Klub</strong>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($branding['logo_path'])): ?>
                                <img src="<?= url($branding['logo_path']) ?>" alt="Logo"
                                     style="max-height:40px;" class="mb-2">
                            <?php endif; ?>
                            <p class="mb-1 text-muted" id="previewMotto">
                                <em><?= View::e($branding['motto'] ?? 'Motto klubu') ?></em>
                            </p>
                            <span class="badge" id="previewAccentBadge"
                                  style="background-color: <?= View::e($branding['accent_color'] ?? '#198754') ?>">
                                Kolor akcentowy
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= url('onboarding/step2') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Wstecz
                </a>
                <button type="submit" class="btn btn-primary">
                    Dalej <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        <div class="text-center mt-3"><a href="<?= url('onboarding/skip') ?>" class="text-muted small">Dokończ później &rarr;</a></div>
</form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var pc = document.getElementById('primary_color');
    var pct = document.getElementById('primary_color_text');
    var ac = document.getElementById('accent_color');
    var act = document.getElementById('accent_color_text');
    var ph = document.getElementById('previewHeader');
    var pab = document.getElementById('previewAccentBadge');
    var motto = document.getElementById('motto');
    var pm = document.getElementById('previewMotto');

    if (pc) pc.addEventListener('input', function() {
        pct.value = pc.value;
        ph.style.backgroundColor = pc.value;
    });
    if (ac) ac.addEventListener('input', function() {
        act.value = ac.value;
        pab.style.backgroundColor = ac.value;
    });
    if (motto) motto.addEventListener('input', function() {
        pm.innerHTML = '<em>' + (motto.value || 'Motto klubu') + '</em>';
    });
});
</script>
