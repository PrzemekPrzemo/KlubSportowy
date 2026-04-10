<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('auth/login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label">Login lub e-mail</label>
        <input type="text" name="username" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">Hasło</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-box-arrow-in-right"></i> Zaloguj
    </button>
</form>
<hr class="my-4">
<p class="text-center mb-0">
    <small class="text-muted">Nie masz konta?</small>
    <a href="<?= url('register') ?>">Zarejestruj swój klub</a>
</p>
