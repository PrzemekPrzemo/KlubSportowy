<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('register') ?>">
    <?= csrf_field() ?>
    <h5 class="mb-3">Rejestracja nowego klubu</h5>
    <div class="mb-3">
        <label class="form-label">Nazwa klubu *</label>
        <input type="text" name="club_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Miasto</label>
        <input type="text" name="city" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">E-mail klubu *</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <hr>
    <h6 class="mb-3">Konto administratora</h6>
    <div class="mb-3">
        <label class="form-label">Imię i nazwisko *</label>
        <input type="text" name="full_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Login *</label>
        <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Hasło * <small class="text-muted">(min. 8 znaków)</small></label>
        <input type="password" name="password" class="form-control" required minlength="8">
    </div>
    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-circle"></i> Zarejestruj klub
    </button>
</form>
<hr class="my-4">
<p class="text-center mb-0"><a href="<?= url('auth/login') ?>">&larr; Wróć do logowania</a></p>
