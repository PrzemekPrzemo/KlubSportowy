<?php
use App\Helpers\Csrf;
use App\Helpers\View;
/** @var array $data */
?>
<section class="py-5" style="background:#f6f7fb;min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <?php include __DIR__ . '/_progress.php'; ?>

                        <h2 class="mb-1">Dane klubu</h2>
                        <p class="text-muted">Zacznijmy od podstawowych informacji o Twoim klubie.</p>

                        <?php if (!empty($flashError)): ?>
                            <div class="alert alert-danger"><?= View::e($flashError) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($flashWarning)): ?>
                            <div class="alert alert-warning"><?= View::e($flashWarning) ?></div>
                        <?php endif; ?>

                        <form method="post" action="<?= url('trial/club-data') ?>" novalidate>
                            <?= Csrf::field() ?>

                            <div class="mb-3">
                                <label class="form-label">Nazwa klubu <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-lg"
                                       value="<?= View::e($data['name'] ?? '') ?>"
                                       maxlength="120" minlength="3" required
                                       placeholder="np. KS Sokol Warszawa">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Miasto <span class="text-danger">*</span></label>
                                    <input type="text" name="city" class="form-control"
                                           value="<?= View::e($data['city'] ?? '') ?>" required
                                           placeholder="Warszawa">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">NIP <small class="text-muted">(opcjonalnie)</small></label>
                                    <input type="text" name="nip" class="form-control"
                                           value="<?= View::e($data['nip'] ?? '') ?>"
                                           maxlength="13" placeholder="1234567890">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email klubu <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control"
                                           value="<?= View::e($data['email'] ?? '') ?>" required
                                           placeholder="kontakt@twojklub.pl">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Telefon <small class="text-muted">(opcjonalnie)</small></label>
                                    <input type="tel" name="phone" class="form-control"
                                           value="<?= View::e($data['phone'] ?? '') ?>"
                                           placeholder="+48 600 000 000">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?= __('wizard.default_locale.title') ?></label>
                                <div class="form-text mb-2"><?= __('wizard.default_locale.help') ?></div>
                                <?php $defLoc = $data['default_locale'] ?? 'pl'; ?>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="default_locale"
                                            id="wiz_locale_pl" value="pl"
                                            <?= $defLoc === 'pl' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="wiz_locale_pl">Polski (PL)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="default_locale"
                                            id="wiz_locale_en" value="en"
                                            <?= $defLoc === 'en' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="wiz_locale_en">English (EN)</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Kod polecajacy <small class="text-muted">(opcjonalnie)</small>
                                </label>
                                <input type="text" name="referral_code" class="form-control"
                                       value="<?= View::e($referralCode ?? '') ?>"
                                       maxlength="20" placeholder="np. KLUB-AB12CD"
                                       style="text-transform:uppercase;">
                                <div class="form-text">
                                    Jesli ktos Cie polecil, wpisz tu jego kod — obaj otrzymacie korzysci.
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="<?= url('trial') ?>" class="btn btn-link text-muted">
                                    <i class="bi bi-arrow-left"></i> Wroc do landingu
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Dalej <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <p class="text-center text-muted small mt-3">
                    Masz juz konto? <a href="<?= url('auth/login') ?>">Zaloguj sie</a>
                </p>
            </div>
        </div>
    </div>
</section>
