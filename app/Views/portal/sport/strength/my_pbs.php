<?php
use App\Helpers\View;
/** @var string $sportKey */
/** @var array $manifest */
/** @var ?array $profile */
/** @var array $pbs */
/** @var array $recent */

$pbMap = [];
foreach ($pbs as $p) {
    $pbMap[$p['lift_type']] = (float)$p['best_kg'];
}
$squat    = $pbMap['squat']    ?? ($profile['squat_pb_kg'] ?? null);
$bench    = $pbMap['bench']    ?? ($profile['bench_pb_kg'] ?? null);
$deadlift = $pbMap['deadlift'] ?? ($profile['deadlift_pb_kg'] ?? null);
$total    = ($squat ?? 0) + ($bench ?? 0) + ($deadlift ?? 0);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1"><i class="bi bi-shield-shaded text-warning me-2"></i><?= View::e($manifest['name'] ?? $sportKey) ?> — moje PB</h3>
        <p class="text-muted mb-0">Osobiste rekordy + ostatnie podejścia.</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-primary">
            <div class="card-body text-center">
                <div class="text-muted small">SQUAT</div>
                <div class="display-6"><?= $squat !== null ? number_format($squat, 1) : '—' ?></div>
                <div class="small">kg</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-success">
            <div class="card-body text-center">
                <div class="text-muted small">BENCH</div>
                <div class="display-6"><?= $bench !== null ? number_format($bench, 1) : '—' ?></div>
                <div class="small">kg</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-danger">
            <div class="card-body text-center">
                <div class="text-muted small">DEADLIFT</div>
                <div class="display-6"><?= $deadlift !== null ? number_format($deadlift, 1) : '—' ?></div>
                <div class="small">kg</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm bg-warning">
            <div class="card-body text-center">
                <div class="text-muted small">TOTAL</div>
                <div class="display-6"><strong><?= number_format($total, 1) ?></strong></div>
                <div class="small">kg</div>
            </div>
        </div>
    </div>
</div>

<?php if ($profile): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">Profil siły</div>
    <div class="card-body small">
        <div class="row">
            <div class="col-md-3">Kategoria wagowa: <strong><?= View::e($profile['weight_class'] ?? '—') ?></strong></div>
            <div class="col-md-3">Masa ciała: <strong><?= $profile['body_weight_kg'] ? number_format((float)$profile['body_weight_kg'], 1) . ' kg' : '—' ?></strong></div>
            <div class="col-md-3">Wilks: <strong><?= $profile['wilks_score'] ? number_format((float)$profile['wilks_score'], 2) : '—' ?></strong></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <i class="bi bi-list-ul me-1"></i> Ostatnie podejścia
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Lift</th>
                    <th>Nr</th>
                    <th>Waga</th>
                    <th>Powt.</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recent as $a): ?>
                <tr>
                    <td class="small"><?= View::e($a['attempted_at']) ?></td>
                    <td><?= View::e($a['lift_type']) ?></td>
                    <td><?= (int)$a['attempt_number'] ?></td>
                    <td><?= $a['weight_kg'] !== null ? number_format((float)$a['weight_kg'], 1) . ' kg' : '—' ?></td>
                    <td><?= (int)$a['reps'] ?></td>
                    <td>
                        <?php if ((int)$a['success'] === 1): ?>
                            <span class="badge bg-success">OK</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">FAIL</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recent): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">Brak zapisanych podejść.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
