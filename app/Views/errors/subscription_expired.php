<?php use App\Helpers\View; ?>
<div class="card p-5 text-center">
    <i class="bi bi-lock display-1 text-danger"></i>
    <h3 class="mt-3">Subskrypcja wygasła</h3>
    <p class="text-muted">Aby kontynuować pracę z systemem, skontaktuj się z administratorem platformy w celu odnowienia subskrypcji.</p>
    <div>
        <a href="<?= url('auth/logout') ?>" class="btn btn-outline-secondary">Wyloguj się</a>
    </div>
</div>
