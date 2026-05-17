<?php
/**
 * Portal: pelna kartoteka MMA zawodnika.
 *
 * Zmienne:
 *   - $mmaCard — sport_mma_member_record (lub null)
 */
use App\Helpers\View;
if (!isset($mmaCard)) $mmaCard = null;
?>
<?php if ($mmaCard): ?>
<div class="card shadow-sm mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-card-text me-1"></i> Moja kartoteka MMA
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3 col-6">
                <div class="text-muted small">Stance</div>
                <div class="fw-bold text-capitalize"><?= View::e($mmaCard['stance']) ?></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Akt. kat. wagowa</div>
                <div class="fw-bold"><?= View::e($mmaCard['current_weight_class'] ?? '—') ?></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Reach (cm)</div>
                <div class="fw-bold"><?= View::e($mmaCard['reach_cm'] ?? '—') ?></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">KO / Sub / Dec</div>
                <div class="fw-bold font-monospace">
                    <?= (int)$mmaCard['ko_wins'] ?> / <?= (int)$mmaCard['sub_wins'] ?> / <?= (int)$mmaCard['dec_wins'] ?>
                </div>
            </div>
        </div>

        <hr>
        <div class="text-muted small mb-2">Discipline mix</div>
        <?php
            $s = (int)$mmaCard['pct_striking'];
            $w = (int)$mmaCard['pct_wrestling'];
            $g = (int)$mmaCard['pct_grappling'];
        ?>
        <div class="progress" style="height: 28px;">
            <div class="progress-bar bg-danger"  style="width: <?= $s ?>%;"  title="Striking">Striking <?= $s ?>%</div>
            <div class="progress-bar bg-primary" style="width: <?= $w ?>%;" title="Wrestling">Wrestling <?= $w ?>%</div>
            <div class="progress-bar bg-success" style="width: <?= $g ?>%;" title="Grappling">Grappling <?= $g ?>%</div>
        </div>
    </div>
</div>
<?php endif; ?>
