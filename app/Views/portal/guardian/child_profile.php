<?php
use App\Helpers\View;
?>
<a href="<?= View::e(url('portal/guardian/children')) ?>" class="small text-decoration-none">
    <i class="bi bi-arrow-left"></i> Wroc
</a>

<div class="gp-card mt-2">
    <div class="d-flex align-items-center gap-3">
        <div class="gp-avatar" style="width:64px;height:64px;font-size:1.4rem;">
            <?= View::e(mb_strtoupper(mb_substr((string)($member['first_name'] ?? '?'), 0, 1))) ?>
        </div>
        <div>
            <h2 class="h4 mb-0"><?= View::e(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?></h2>
            <div class="small text-muted">
                Nr klubowy: <?= View::e($member['member_number'] ?? '—') ?>
                <?php if (!empty($member['birth_date'])): ?>
                    &middot; ur. <?= View::e($member['birth_date']) ?>
                <?php endif; ?>
                <?php if (!empty($member['status'])): ?>
                    &middot; status: <?= View::e($member['status']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="gp-card">
    <div class="d-grid gap-2">
        <a href="<?= View::e(url('portal/guardian/child/' . (int)$member['id'] . '/consents')) ?>" class="btn btn-outline-primary text-start">
            <i class="bi bi-shield-check"></i> Zgody RODO art. 8
            <i class="bi bi-chevron-right float-end"></i>
        </a>
        <a href="<?= View::e(url('portal/guardian/child/' . (int)$member['id'] . '/payments')) ?>" class="btn btn-outline-secondary text-start">
            <i class="bi bi-credit-card"></i> Platnosci i skladki
            <i class="bi bi-chevron-right float-end"></i>
        </a>
    </div>
</div>

<?php if (!empty($medical)): ?>
<div class="gp-card">
    <h3 class="h6">Badania lekarskie</h3>
    <ul class="list-unstyled small mb-0">
        <?php foreach ($medical as $m): ?>
            <li class="d-flex justify-content-between border-bottom py-1">
                <span><?= View::e($m['exam_type'] ?? 'Badanie') ?></span>
                <span class="text-muted">
                    do <?= View::e($m['valid_until'] ?? '—') ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<p class="small text-muted text-center mt-3">
    Dane osobowe dziecka sa <strong>tylko do odczytu</strong>.
    Aby je zmienic, skontaktuj sie z klubem.
</p>
