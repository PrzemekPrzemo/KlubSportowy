<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('auth/login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label"><?= __('auth.login_or_email') ?></label>
        <input type="text" name="username" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= __('auth.password') ?></label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-box-arrow-in-right"></i> <?= __('auth.login_btn') ?>
    </button>
</form>
<hr class="my-4">
<p class="text-center mb-0">
    <small class="text-muted"><?= __('auth.no_account') ?></small>
    <a href="<?= url('register') ?>"><?= __('auth.register_club') ?></a>
</p>
