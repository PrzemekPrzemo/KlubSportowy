<?php
/**
 * Index manualu — hero + kafelki stron pogrupowane po `group`.
 *
 * @var array  $manual    Definicja manualu (label, baseUrl, icon, desc, intro, pages)
 * @var string $manualKey
 * @var array<string, array<int, array{slug:string,title:string,icon?:string,desc?:string,group?:string}>> $groups
 */

use App\Helpers\View;

$totalPages = array_sum(array_map('count', $groups));
?>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="small mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= url('help') ?>">Pomoc</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= View::e($manual['label']) ?></li>
        </ol>
    </nav>

    <div class="p-4 p-md-5 mb-4 rounded-3" style="background: linear-gradient(135deg, #232232 0%, #3d3a52 100%); color:#fff;">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <i class="bi <?= View::e($manual['icon']) ?>" style="font-size:3.5rem; color:#EE2C28;"></i>
            </div>
            <div class="col">
                <h1 class="display-6 fw-bold mb-1"><?= View::e($manual['label']) ?></h1>
                <p class="lead mb-1 text-white-50"><?= View::e($manual['intro']) ?></p>
                <p class="small mb-0 text-white-50">
                    <i class="bi bi-collection"></i> <?= (int)$totalPages ?> stron
                    <span class="ms-3"><i class="bi bi-chat-square-quote"></i> Język polski, przyjazny</span>
                    <span class="ms-3"><i class="bi bi-image"></i> Mockupy ekranów</span>
                </p>
            </div>
        </div>
    </div>

    <?php $groupNo = 0; foreach ($groups as $groupName => $pages): $groupNo++; ?>
        <h2 class="h5 mt-4 mb-3">
            <span class="badge bg-light text-dark border me-2"><?= $groupNo ?></span>
            <?= View::e($groupName) ?>
        </h2>
        <div class="row g-3 mb-3">
            <?php foreach ($pages as $p): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="<?= url($manual['baseUrl'] . '/' . $p['slug']) ?>"
                       class="card h-100 text-decoration-none text-body"
                       style="border:1px solid #e5e7eb;">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi <?= View::e($p['icon'] ?? 'bi-file-text') ?> fs-4 text-primary"></i>
                                <h6 class="card-title mb-0"><?= View::e($p['title']) ?></h6>
                            </div>
                            <?php if (!empty($p['desc'])): ?>
                                <p class="card-text text-muted small mb-0"><?= View::e($p['desc']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0 pb-3">
                            <small class="text-primary">Czytaj <i class="bi bi-arrow-right"></i></small>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="alert alert-info mt-4 small mb-0">
        <i class="bi bi-info-circle"></i>
        Manual jest aktualizowany regularnie. Jeśli czegoś brakuje — napisz do nas na
        <a href="mailto:support@clubdesk.pl">support@clubdesk.pl</a>.
    </div>
</div>
