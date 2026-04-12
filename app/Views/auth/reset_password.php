<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('auth/reset-password') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= View::e($token ?? '') ?>">
    <p class="text-muted small mb-3">
        Wprowadz nowe haslo do swojego konta.
    </p>
    <div class="mb-3">
        <label class="form-label">Nowe haslo</label>
        <input type="password" name="password" class="form-control" required minlength="8"
               placeholder="Min. 8 znakow">
    </div>
    <div class="mb-3">
        <label class="form-label">Powtorz haslo</label>
        <input type="password" name="password_confirm" class="form-control" required minlength="8">
    </div>
    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-shield-lock"></i> Ustaw nowe haslo
    </button>
</form>
<hr class="my-4">
<p class="text-center mb-0">
    <a href="<?= url('auth/login') ?>"><i class="bi bi-arrow-left"></i> Powrot do logowania</a>
</p>
