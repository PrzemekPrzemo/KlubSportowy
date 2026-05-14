<?php
/**
 * @var array<string, array{file:string,title:string,icon:string,desc:string,available:bool}> $sections
 */
use App\Helpers\View;
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="mb-1"><i class="bi bi-question-circle"></i> Centrum pomocy</h1>
            <p class="text-muted mb-0">Wszystkie dokumenty, których potrzebujesz, w jednym miejscu — bez czytania surowego markdownu.</p>
        </div>
        <form class="d-flex" role="search" onsubmit="return false;">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" id="help-search-input" class="form-control" placeholder="Szukaj w pomocy (TODO)…" disabled>
            </div>
        </form>
    </div>

    <div class="row g-3">
        <?php foreach ($sections as $slug => $sec): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= url('help/' . $slug) ?>"
                   class="card h-100 text-decoration-none text-body <?= $sec['available'] ? '' : 'opacity-50' ?>"
                   style="border:1px solid #e5e7eb;">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= View::e($sec['icon']) ?> fs-3 text-primary"></i>
                            <h5 class="card-title mb-0"><?= View::e($sec['title']) ?></h5>
                        </div>
                        <p class="card-text text-muted small mb-0"><?= View::e($sec['desc']) ?></p>
                        <?php if (!$sec['available']): ?>
                            <span class="badge bg-warning text-dark mt-2">Wkrótce</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0 pb-3">
                        <small class="text-primary">Otwórz <i class="bi bi-arrow-right"></i></small>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info mt-4 small mb-0">
        <i class="bi bi-info-circle"></i>
        Nie znalazłeś odpowiedzi? Napisz do nas na
        <a href="mailto:support@clubdesk.pl">support@clubdesk.pl</a>.
    </div>
</div>
