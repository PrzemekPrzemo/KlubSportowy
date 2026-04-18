<?php use App\Helpers\View; ?>

<div class="card">
    <div class="card-body p-4">
        <h4 class="mb-1"><i class="bi bi-check-circle"></i> Podsumowanie</h4>
        <p class="text-muted mb-4">Wszystko gotowe! Sprawdź dane swojego klubu.</p>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border h-100">
                    <div class="card-body text-center">
                        <div class="fs-1 text-primary mb-2"><i class="bi bi-building"></i></div>
                        <h5><?= View::e($club['name'] ?? '') ?></h5>
                        <?php if (!empty($club['city'])): ?>
                            <p class="text-muted mb-0"><?= View::e($club['city']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border h-100">
                    <div class="card-body text-center">
                        <div class="fs-1 text-success mb-2"><i class="bi bi-trophy"></i></div>
                        <h5><?= (int)($stats['sports'] ?? 0) ?> <?= ((int)($stats['sports'] ?? 0) === 1) ? 'sport' : 'sportów' ?></h5>
                        <p class="text-muted mb-0">Aktywne sekcje</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border h-100">
                    <div class="card-body text-center">
                        <div class="fs-1 text-info mb-2"><i class="bi bi-people"></i></div>
                        <h5><?= (int)($stats['members'] ?? 0) ?> zawodników</h5>
                        <p class="text-muted mb-0">Aktywni członkowie</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branding preview -->
        <div class="mb-4">
            <h6>Branding</h6>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <?php if (!empty($branding['logo_path'])): ?>
                    <img src="<?= url($branding['logo_path']) ?>" alt="Logo"
                         style="max-height:50px;" class="rounded">
                <?php endif; ?>
                <div class="d-flex align-items-center gap-2">
                    <span style="display:inline-block; width:28px; height:28px; border-radius:6px; background:<?= View::e($branding['primary_color'] ?? '#0d6efd') ?>;"
                          title="Kolor podstawowy"></span>
                    <span class="small text-muted"><?= View::e($branding['primary_color'] ?? '#0d6efd') ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="display:inline-block; width:28px; height:28px; border-radius:6px; background:<?= View::e($branding['accent_color'] ?? '#198754') ?>;"
                          title="Kolor akcentowy"></span>
                    <span class="small text-muted"><?= View::e($branding['accent_color'] ?? '#198754') ?></span>
                </div>
                <?php if (!empty($branding['motto'])): ?>
                    <span class="badge bg-secondary"><em><?= View::e($branding['motto']) ?></em></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="<?= url('onboarding/step4') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Wstecz
            </a>
            <form method="POST" action="<?= url('onboarding/complete') ?>" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-rocket-takeoff"></i> Przejdź do panelu
                </button>
            <div class="text-center mt-3"><a href="<?= url('onboarding/skip') ?>" class="text-muted small">Dokończ później &rarr;</a></div>
</form>
        </div>
    </div>
</div>
