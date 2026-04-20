<?php
use App\Helpers\View;
use App\Sports\Triathlon\Models\TriathlonResultModel;
?>

<?php if (!empty($pbs)): ?>
<div class="card p-3 mb-3">
    <h6 class="mb-3"><i class="bi bi-trophy me-1"></i>Rekordy osobiste (PB)</h6>
    <div class="row g-2">
        <?php foreach ($pbs as $dist => $pb): ?>
        <div class="col-sm-6">
            <div class="border rounded p-2">
                <div class="text-muted small fw-bold"><?= strtoupper($dist) ?></div>
                <div class="fs-5 fw-bold"><?= TriathlonResultModel::formatTime((int)$pb['total_time']) ?></div>
                <div class="small text-muted d-flex gap-3">
                    <span>🏊 <?= TriathlonResultModel::formatTime((int)$pb['swim_time']) ?></span>
                    <span>🚴 <?= TriathlonResultModel::formatTime((int)$pb['bike_time']) ?></span>
                    <span>🏃 <?= TriathlonResultModel::formatTime((int)$pb['run_time']) ?></span>
                </div>
                <div class="text-muted small"><?= View::e($pb['event_name']) ?> · <?= View::e($pb['event_date']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Brak wyników triatlonowych.</div>
<?php endif; ?>

<?php if (!empty($recentResults)): ?>
<div class="card p-3">
    <h6 class="mb-3"><i class="bi bi-clock-history me-1"></i>Ostatnie starty</h6>
    <?php foreach ($recentResults as $r): ?>
        <div class="border-bottom py-2 d-flex justify-content-between align-items-center">
            <div>
                <strong><?= View::e($r['event_name']) ?></strong>
                <div class="small text-muted"><?= View::e($r['event_date']) ?> · <?= strtoupper($r['distance_type']) ?>
                    <?php if ($r['age_group']): ?> · <?= View::e($r['age_group']) ?><?php endif; ?>
                </div>
            </div>
            <div class="text-end">
                <?php if ($r['dnf']): ?>
                    <span class="badge bg-danger">DNF</span>
                <?php elseif ($r['dns']): ?>
                    <span class="badge bg-secondary">DNS</span>
                <?php else: ?>
                    <div class="fw-bold"><?= TriathlonResultModel::formatTime((int)$r['total_time']) ?></div>
                    <?php if ($r['ag_placement']): ?><small class="text-muted">AG #<?= (int)$r['ag_placement'] ?></small><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
