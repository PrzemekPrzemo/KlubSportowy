<?php
use App\Helpers\Csrf;
use App\Helpers\View;
/** @var array $data */
/** @var string $clubName */
$suggested = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim((string)$clubName)));
$suggested = trim((string)$suggested, '-');
?>
<section class="py-5" style="background:#f6f7fb;min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <?php include __DIR__ . '/_progress.php'; ?>

                        <h2 class="mb-1">Branding</h2>
                        <p class="text-muted">Zaprojektuj wyglad i adres swojego klubu.</p>

                        <?php if (!empty($flashError)): ?>
                            <div class="alert alert-danger"><?= View::e($flashError) ?></div>
                        <?php endif; ?>

                        <form method="post" action="<?= url('trial/branding') ?>" enctype="multipart/form-data">
                            <?= Csrf::field() ?>

                            <div class="mb-3">
                                <label class="form-label">Subdomena <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="subdomain" class="form-control"
                                           value="<?= View::e($data['subdomain'] ?? $suggested) ?>"
                                           pattern="[a-z0-9-]{3,40}" required
                                           placeholder="twojklub">
                                    <span class="input-group-text">.clubdesk.pl</span>
                                </div>
                                <small class="text-muted">3-40 znakow, male litery / cyfry / mysliniki.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kolor podstawowy</label>
                                    <input type="color" name="primary_color" class="form-control form-control-color"
                                           value="<?= View::e($data['primary_color'] ?? '#EE2C28') ?>" style="width:100%;height:42px;">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kolor akcentu</label>
                                    <input type="color" name="accent_color" class="form-control form-control-color"
                                           value="<?= View::e($data['accent_color'] ?? '#198754') ?>" style="width:100%;height:42px;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Logo klubu <small class="text-muted">(PNG/JPG/WEBP, max 2MB)</small></label>
                                <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/webp">
                                <?php if (!empty($data['logo_path'])): ?>
                                    <small class="text-success d-block mt-1">
                                        <i class="bi bi-check-circle"></i> Logo juz przeslane (<?= View::e($data['logo_path']) ?>)
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Motto / haslo klubu <small class="text-muted">(opcjonalnie)</small></label>
                                <input type="text" name="motto" class="form-control" maxlength="255"
                                       value="<?= View::e($data['motto'] ?? '') ?>"
                                       placeholder="np. Razem na podium!">
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="<?= url('trial/start') ?>" class="btn btn-link text-muted">
                                    <i class="bi bi-arrow-left"></i> Wstecz
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Dalej <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
