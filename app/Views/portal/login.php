<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('portal/login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">Hasło</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success w-100">
        <i class="bi bi-box-arrow-in-right"></i> Zaloguj
    </button>
</form>
<hr class="my-4">
<p class="text-center mb-0 small">
    <a href="<?= url('auth/login') ?>">Jesteś pracownikiem klubu? Zaloguj się tutaj &rarr;</a>
</p>
