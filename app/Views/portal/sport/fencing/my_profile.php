<?php
/**
 * Portal: rozszerzony profil szermierza (multi-weapon + FIE rank).
 *
 * Zmienne:
 *   - $multiArmed — sport_fencing_member (lub null)
 */
use App\Helpers\View;
use App\Sports\Fencing\Models\FencingMemberModel;
if (!isset($multiArmed)) $multiArmed = null;

$weaponList = [];
if ($multiArmed && !empty($multiArmed['weapons_list'] ?? [])) {
    $weaponList = $multiArmed['weapons_list'];
} elseif ($multiArmed && !empty($multiArmed['weapons'])) {
    $weaponList = array_filter(array_map('trim', explode(',', $multiArmed['weapons'])));
}
?>
<?php if ($multiArmed): ?>
<div class="card shadow-sm mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-shield-fill me-1"></i> Moje bronie i ranking FIE
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">Bronie</div>
                <?php if ($weaponList): ?>
                    <?php foreach ($weaponList as $w):
                        $info = FencingMemberModel::$WEAPONS[$w] ?? ['label' => $w, 'color' => '#888'];
                    ?>
                        <span class="badge me-1" style="background:<?= $info['color'] ?>;color:#fff;">
                            <?= View::e($info['label']) ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">FIE rank</div>
                <div class="fw-bold font-monospace">
                    <?= $multiArmed['fie_rank'] !== null ? '#' . (int)$multiArmed['fie_rank'] : '—' ?>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Reka</div>
                <div class="fw-bold">
                    <?= View::e(FencingMemberModel::$HANDS[$multiArmed['hand']] ?? '—') ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
