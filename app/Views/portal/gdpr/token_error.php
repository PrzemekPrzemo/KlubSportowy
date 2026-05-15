<?php use App\Helpers\View; ?>

<div class="row justify-content-center mt-5">
    <div class="col-lg-6">
        <div class="card border-danger text-center">
            <div class="card-body p-5">
                <i class="bi bi-x-octagon-fill fs-1 text-danger mb-3"></i>
                <h4>Blad weryfikacji</h4>
                <p class="text-muted"><?= View::e($message ?? 'Nieprawidlowy link.') ?></p>
                <a href="<?= url('portal/login') ?>" class="btn btn-outline-secondary">Powrot do logowania</a>
            </div>
        </div>
    </div>
</div>
