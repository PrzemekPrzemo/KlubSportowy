<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-images text-primary me-2"></i>
        Logo sekcji: <?= View::e($clubSport['sport_name'] ?? '—') ?>
        <?php if (!empty($clubSport['name'])): ?>
            <small class="text-muted">(<?= View::e($clubSport['name']) ?>)</small>
        <?php endif; ?>
    </h3>
    <a href="<?= url('sports') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wróć do sekcji
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
    Logo sekcji sportowej pojawia się na <strong>dokumentach (PDF, raporty)</strong>
    obok logo systemu i logo klubu. Wgraj 1-3 warianty per sekcja
    (główne / alternatywne / na ciemnym tle). To opcjonalne — jeśli
    nie ustawisz, dokumenty użyją tylko logo klubu.
</div>

<form method="POST" action="<?= url('sports/' . (int)$clubSport['id'] . '/logos/save') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-3">
        <?php foreach ([
            'main' => ['Główne',        'Domyślne — używane na większości dokumentów'],
            'alt'  => ['Alternatywne',  'Wariant uproszczony lub mono'],
            'dark' => ['Ciemne tło',    'Jasna wersja dla nagłówków na czarno'],
        ] as $variant => [$label, $desc]):
            $field = "logo_{$variant}_path";
            $current = $clubSport[$field] ?? null;
        ?>
            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <h5 class="mb-1"><?= View::e($label) ?></h5>
                    <small class="text-muted mb-2"><?= View::e($desc) ?></small>

                    <div class="<?= $variant === 'dark' ? 'bg-dark' : 'bg-light' ?> p-3 rounded mb-2 text-center"
                         style="min-height:100px; display:flex; align-items:center; justify-content:center;">
                        <?php if ($current): ?>
                            <img src="<?= url($current) ?>" style="max-width:100%; max-height:80px;">
                        <?php else: ?>
                            <span class="text-muted small">(brak)</span>
                        <?php endif; ?>
                    </div>

                    <input type="file" name="logo_<?= $variant ?>" class="form-control form-control-sm" accept=".png,.jpg,.jpeg,.webp,.svg">
                    <small class="text-muted mt-1">PNG / JPG / WebP / SVG, max 2 MB</small>

                    <?php if ($current): ?>
                        <div class="form-check mt-2">
                            <input type="checkbox" name="reset_<?= $variant ?>" id="reset_<?= $variant ?>" class="form-check-input">
                            <label for="reset_<?= $variant ?>" class="form-check-label small">Usuń obecne</label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Zapisz zmiany
        </button>
    </div>
</form>
