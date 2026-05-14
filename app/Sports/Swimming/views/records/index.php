<?php
use App\Helpers\View;
use App\Sports\Swimming\Models\SwimmingResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Rekordy klubu — Pływanie</h4>
    <a href="<?= url('swimming/results') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wyniki
    </a>
</div>

<?php if (empty($grouped)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i> Brak rekordów. Dodaj wyniki w zakładce <a href="<?= url('swimming/results') ?>">Wyniki</a>.
    </div>
<?php else: ?>
    <?php foreach ($grouped as $poolType => $records): ?>
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
                <i class="bi bi-water"></i>
                <strong><?= View::e($poolTypes[$poolType] ?? $poolType) ?></strong>
                <span class="badge bg-light text-primary ms-auto"><?= count($records) ?> rekordów</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Styl</th>
                            <th>Dystans</th>
                            <th>Czas</th>
                            <th>Zawodnik</th>
                            <th>Data</th>
                            <th>Zawody</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td><strong><?= View::e($strokes[$r['stroke']] ?? $r['stroke']) ?></strong></td>
                                <td><span class="badge bg-secondary"><?= (int)$r['distance_m'] ?> m</span></td>
                                <td class="font-monospace fw-bold text-success">
                                    <?= SwimmingResultModel::formatTime((int)$r['time_ms']) ?>
                                </td>
                                <td>
                                    <?= View::e($r['last_name'] . ' ' . $r['first_name']) ?>
                                    <small class="text-muted">#<?= View::e($r['member_number']) ?></small>
                                </td>
                                <td class="text-muted small"><?= View::e($r['score_date']) ?></td>
                                <td class="text-muted"><?= View::e($r['competition_name'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
