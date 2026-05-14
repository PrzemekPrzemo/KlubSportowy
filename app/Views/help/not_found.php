<?php
/** @var array $sections */
use App\Helpers\View;
?>
<div class="container py-5 text-center">
    <i class="bi bi-question-diamond text-warning" style="font-size: 4rem;"></i>
    <h1 class="mt-3">Strona pomocy nie znaleziona</h1>
    <p class="text-muted">Sprawdź listę dostępnych sekcji poniżej lub wróć do centrum pomocy.</p>
    <a href="<?= url('help') ?>" class="btn btn-primary mt-2">
        <i class="bi bi-house"></i> Centrum pomocy
    </a>
    <div class="row g-3 mt-4 text-start">
        <?php foreach ($sections as $slug => $sec): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= url('help/' . $slug) ?>" class="card h-100 text-decoration-none text-body">
                    <div class="card-body">
                        <h6 class="mb-1"><i class="bi <?= View::e($sec['icon']) ?>"></i> <?= View::e($sec['title']) ?></h6>
                        <small class="text-muted"><?= View::e($sec['desc']) ?></small>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
