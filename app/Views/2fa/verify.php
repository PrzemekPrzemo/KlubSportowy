<?php use App\Helpers\View; ?>
<h5 class="text-center mb-3"><i class="bi bi-shield-lock"></i> Weryfikacja dwuetapowa</h5>
<p class="small text-muted text-center">Wpisz kod z aplikacji 2FA lub jeden z kodów zapasowych.</p>
<form method="POST" action="<?= url('2fa/verify') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <input type="text" name="code" class="form-control form-control-lg text-center" maxlength="8" required autofocus>
    </div>
    <button class="btn btn-primary w-100">
        <i class="bi bi-unlock"></i> Zaloguj
    </button>
</form>
<hr class="my-4">
<p class="text-center mb-0">
    <a href="<?= url('auth/logout') ?>" class="small text-muted">Anuluj i wróć do logowania</a>
</p>
