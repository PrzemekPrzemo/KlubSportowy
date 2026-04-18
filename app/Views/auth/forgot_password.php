<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('auth/forgot-password') ?>">
    <?= csrf_field() ?>
    <p class="text-muted small mb-3">
        Podaj adres e-mail powiazany z Twoim kontem. Wyslemy Ci link do resetowania hasla.
    </p>
    <div class="mb-3">
        <label class="form-label">Adres e-mail</label>
        <input type="email" name="email" class="form-control" required autofocus
               placeholder="twoj@email.pl">
    </div>
    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-envelope"></i> Wyslij link resetujacy
    </button>
</form>
<hr class="my-4">
<p class="text-center mb-0">
    <a href="<?= url('auth/login') ?>"><i class="bi bi-arrow-left"></i> Powrot do logowania</a>
</p>
