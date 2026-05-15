<?php use App\Helpers\View; ?>

<div class="card">
    <div class="card-body p-4">
        <h4 class="mb-1"><i class="bi bi-building"></i> <?= __('onboard.step1.title') ?></h4>
        <p class="text-muted mb-4"><?= __('onboard.step1.intro') ?></p>

        <form method="POST" action="<?= url('onboarding/step1') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="name" class="form-label"><?= __('onboard.step1.name') ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name"
                       value="<?= View::e($club['name'] ?? '') ?>" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="city" class="form-label"><?= __('onboard.step1.city') ?></label>
                    <input type="text" class="form-control" id="city" name="city"
                           value="<?= View::e($club['city'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="nip" class="form-label"><?= __('onboard.step1.nip') ?></label>
                    <input type="text" class="form-control" id="nip" name="nip"
                           value="<?= View::e($club['nip'] ?? '') ?>" placeholder="1234567890">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="email" class="form-label"><?= __('onboard.step1.email') ?></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= View::e($club['email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label"><?= __('onboard.step1.phone') ?></label>
                    <input type="text" class="form-control" id="phone" name="phone"
                           value="<?= View::e($club['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <?= __('onboard.next') ?> <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        <div class="text-center mt-3"><a href="<?= url('onboarding/skip') ?>" class="text-muted small"><?= __('onboard.skip_later') ?></a></div>
</form>
    </div>
</div>
