<?php use App\Helpers\View; ?>

<div class="row g-4">
    <!-- Karta zawodnika -->
    <div class="col-md-6">
        <div class="card shadow border-0" style="background:linear-gradient(135deg,#232232 60%,#EE2C28 100%);color:#fff;border-radius:1.2rem;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <?php if (!empty($club['logo_path'])): ?>
                            <img src="<?= url($club['logo_path']) ?>" alt="logo" style="height:40px;opacity:.9;">
                        <?php else: ?>
                            <span class="fw-bold fs-5 opacity-75"><?= View::e($club['name'] ?? '') ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-light text-dark fs-6"><?= View::e($member['member_number'] ?? '') ?></span>
                </div>

                <div class="d-flex align-items-center gap-3 mb-3">
                    <?php if (!empty($member['photo_path'])): ?>
                        <img src="<?= url($member['photo_path']) ?>" alt="foto"
                             class="rounded-circle border border-3 border-white"
                             style="width:80px;height:80px;object-fit:cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center border border-3 border-white"
                             style="width:80px;height:80px;font-size:2rem;flex-shrink:0;">
                            <i class="bi bi-person-fill text-white"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="fw-bold fs-5"><?= View::e($member['first_name'] . ' ' . $member['last_name']) ?></div>
                        <?php if (!empty($member['birth_date'])): ?>
                            <div class="small opacity-75">ur. <?= View::e($member['birth_date']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($member['sports'])): ?>
                            <div class="mt-1">
                                <?php foreach ($member['sports'] as $sp): ?>
                                    <?php if ($sp['is_active']): ?>
                                        <span class="badge bg-danger me-1 small"><?= View::e($sp['sport_name']) ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($licenses)): ?>
                    <div class="border-top border-white border-opacity-25 pt-2 mt-2">
                        <div class="small opacity-75 mb-1">Aktywne licencje:</div>
                        <?php foreach ($licenses as $lic): ?>
                            <span class="badge bg-success me-1"><?= View::e($lic['sport_name'] ?? '') ?> · <?= View::e($lic['license_number']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="text-end mt-3 opacity-50 small">KlubSportowy · <?= date('Y') ?></div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer"></i> Drukuj kartę
            </button>
        </div>
    </div>

    <!-- QR kod i upload zdjęcia -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-qr-code me-2"></i>Kod QR zawodnika</div>
            <div class="card-body text-center">
                <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($qrData) ?>&size=180x180&margin=6"
                     alt="QR zawodnika" class="img-fluid" style="max-width:180px;"
                     onerror="this.style.display='none';document.getElementById('qr-fallback').style.display='block'">
                <div id="qr-fallback" style="display:none" class="text-muted small mt-2">
                    <i class="bi bi-qr-code fs-1"></i><br>
                    <code><?= View::e($qrData) ?></code>
                </div>
                <div class="text-muted small mt-2"><?= View::e($qrData) ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-camera me-2"></i>Zdjęcie profilowe</div>
            <div class="card-body">
                <?php if (!empty($member['photo_path'])): ?>
                    <div class="text-center mb-3">
                        <img src="<?= url($member['photo_path']) ?>" alt="zdjęcie"
                             class="rounded" style="max-height:120px;max-width:120px;object-fit:cover;">
                    </div>
                <?php endif; ?>
                <form method="POST" action="<?= url('portal/photo-upload') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <input type="file" name="photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" required>
                        <div class="form-text">JPG, PNG lub WebP · max 2 MB</div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-upload me-1"></i> Prześlij zdjęcie
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .portal-nav, .col-md-6:last-child, button, .alert { display: none !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>
