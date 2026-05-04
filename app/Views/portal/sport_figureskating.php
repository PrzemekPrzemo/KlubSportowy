<?php
use App\Helpers\View;
use App\Sports\FigureSkating\Models\FigureSkatingResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-star-half text-primary me-2"></i>Łyżwiarstwo figurowe</h3>
        <p class="text-muted mb-0">Moje wyniki i Personal Best</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if (!empty($bests)): ?>
<div class="card shadow-sm mb-4 border-success">
    <div class="card-header bg-success text-white"><i class="bi bi-trophy-fill me-1"></i> Moje Personal Best</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Dyscyplina</th><th>Level</th><th>Best total</th></tr></thead>
            <tbody>
            <?php foreach ($bests as $b): ?>
                <tr>
                    <td><?= View::e(FigureSkatingResultModel::$DISCIPLINES[$b['discipline']] ?? $b['discipline']) ?></td>
                    <td><?= View::e(FigureSkatingResultModel::$LEVELS[$b['level']] ?? $b['level']) ?></td>
                    <td class="font-monospace fw-bold text-success"><?= number_format((float)$b['best_total'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header">Moje wyniki</div>
    <div class="card-body p-0">
        <?php if (empty($myResults)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wyników.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Zawody</th><th>Dyscyplina</th><th>SP</th><th>FS</th><th>Total</th><th>#</th></tr></thead>
                    <tbody>
                    <?php foreach ($myResults as $r): ?>
                        <tr>
                            <td class="small"><?= View::e($r['event_date']) ?></td>
                            <td class="small"><?= View::e($r['event_name']) ?></td>
                            <td><small><?= View::e(FigureSkatingResultModel::$DISCIPLINES[$r['discipline']] ?? $r['discipline']) ?></small></td>
                            <td class="font-monospace small"><?= $r['sp_total'] !== null ? number_format((float)$r['sp_total'], 2) : '—' ?></td>
                            <td class="font-monospace small"><?= $r['fs_total'] !== null ? number_format((float)$r['fs_total'], 2) : '—' ?></td>
                            <td class="font-monospace fw-bold"><?= $r['total_score'] !== null ? number_format((float)$r['total_score'], 2) : '—' ?></td>
                            <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
