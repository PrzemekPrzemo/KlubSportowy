<?php use App\Helpers\View; ?>
<section class="py-5">
    <div class="container text-center">
        <div class="display-1 text-muted mb-3"><i class="bi bi-file-earmark-x"></i></div>
        <h1 class="mb-3">Nie znaleziono dokumentu</h1>
        <p class="text-muted mb-4">
            Dokument o podanym adresie nie istnieje lub został wycofany.
        </p>
        <a href="<?= url('legal') ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1"></i> Wszystkie dokumenty
        </a>
    </div>
</section>
