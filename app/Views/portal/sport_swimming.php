<?php
use App\Helpers\View;
use App\Sports\Swimming\Models\SwimmingResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-water text-primary me-2"></i>Pływanie</h3>
        <p class="text-muted mb-0">Moje rekordy osobiste i ostatnie wyniki</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<!-- Personal Bests -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-trophy-fill me-1"></i> Moje rekordy osobiste (PB)
    </div>
    <div class="card-body p-0">
        <?php if (empty($personalBests)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wyników. Rekordy pojawią się po pierwszym starcie.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Styl</th><th>Dystans</th><th>Basen</th><th>Czas PB</th><th>Data</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personalBests as $pb): ?>
                            <tr>
                                <td><?= View::e(SwimmingResultModel::$STROKES[$pb['stroke']] ?? $pb['stroke']) ?></td>
                                <td><span class="badge bg-secondary"><?= (int)$pb['distance_m'] ?> m</span></td>
                                <td class="text-muted small"><?= View::e(SwimmingResultModel::$POOL_TYPES[$pb['pool_type']] ?? $pb['pool_type']) ?></td>
                                <td class="font-monospace fw-bold text-success">
                                    <?= SwimmingResultModel::formatTime((int)$pb['time_ms']) ?>
                                </td>
                                <td class="text-muted small"><?= View::e($pb['score_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Results -->
<div class="card shadow-sm">
    <div class="card-header">
        <i class="bi bi-clock-history me-1"></i> Ostatnie wyniki
    </div>
    <div class="card-body p-0">
        <?php if (empty($recent)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wyników.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Zawody</th><th>Styl</th><th>Dystans</th><th>Czas</th><th>Miejsce</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                            <tr>
                                <td class="text-muted small"><?= View::e($r['score_date']) ?></td>
                                <td><?= View::e($r['competition_name'] ?? '—') ?></td>
                                <td><?= View::e(SwimmingResultModel::$STROKES[$r['stroke']] ?? $r['stroke']) ?></td>
                                <td><span class="badge bg-light text-dark"><?= (int)$r['distance_m'] ?>m</span></td>
                                <td class="font-monospace">
                                    <?= SwimmingResultModel::formatTime((int)$r['time_ms']) ?>
                                    <?php if ($r['personal_best']): ?>
                                        <span class="badge bg-warning text-dark ms-1">PB</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['placement']): ?>
                                        <span class="badge bg-primary">#<?= (int)$r['placement'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
