<?php use App\Helpers\View; ?>

<div class="pub-hero text-center mb-4">
    <div class="container">
        <h1><i class="bi bi-trophy"></i> Kluby sportowe</h1>
        <p class="lead mb-0">Przegladaj zarejestrowane kluby i ich sekcje sportowe</p>
    </div>
</div>

<div class="container pb-5">
    <?php if (empty($clubs)): ?>
        <div class="alert alert-info text-center">Brak aktywnych klubow w systemie.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($clubs as $club): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <?php if (!empty($club['logo_path'])): ?>
                                    <img src="<?= url($club['logo_path']) ?>" alt="logo"
                                         style="max-width:50px; max-height:50px; margin-right:.75rem; border-radius:.25rem;">
                                <?php else: ?>
                                    <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center me-3"
                                         style="width:50px; height:50px; font-size:1.3rem;">
                                        <i class="bi bi-building"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h5 class="card-title mb-0"><?= View::e($club['name']) ?></h5>
                                    <?php if (!empty($club['city'])): ?>
                                        <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= View::e($club['city']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($club['motto'])): ?>
                                <p class="text-muted fst-italic small mb-2"><?= View::e($club['motto']) ?></p>
                            <?php endif; ?>

                            <div class="mb-3">
                                <?php foreach ($club['sports'] as $sport): ?>
                                    <span class="sport-badge me-1 mb-1"
                                          style="background: <?= View::e($sport['color'] ?? '#6c757d') ?>">
                                        <?php if (!empty($sport['icon'])): ?>
                                            <i class="bi <?= View::e($sport['icon']) ?>"></i>
                                        <?php endif; ?>
                                        <?= View::e($sport['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-people"></i> <?= (int)$club['member_count'] ?> zawodnikow
                                </small>
                                <?php if (!empty($club['subdomain'])): ?>
                                    <a href="<?= url('pub/' . urlencode($club['subdomain'])) ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        Zobacz <i class="bi bi-arrow-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
