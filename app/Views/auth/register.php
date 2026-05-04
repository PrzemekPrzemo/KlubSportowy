<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('register') ?>">
    <?= csrf_field() ?>
    <h5 class="mb-3"><?= __('auth.register_club_title') ?></h5>
    <div class="mb-3">
        <label class="form-label"><?= __('form.club_name') ?> *</label>
        <input type="text" name="club_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= __('form.city') ?></label>
        <input type="text" name="city" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label"><?= __('form.club_email') ?> *</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <hr>
    <h6 class="mb-3"><?= __('auth.admin_account') ?></h6>
    <div class="mb-3">
        <label class="form-label"><?= __('auth.full_name') ?> *</label>
        <input type="text" name="full_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= __('form.username') ?> *</label>
        <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= __('auth.password_hint') ?> *</label>
        <input type="password" name="password" class="form-control" required minlength="8">
    </div>
    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-circle"></i> <?= __('auth.register_club_btn') ?>
    </button>
</form>
<hr class="my-4">
<p class="text-center mb-0"><a href="<?= url('auth/login') ?>">&larr; <?= __('auth.back_to_login') ?></a></p>
