<?php
use App\Helpers\View;
use App\Sports\Snowboard\Models\SnowboardResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-snow2 text-primary me-2"></i>Snowboard</h3>
        <p class="text-muted mb-0">Moje najlepsze wyniki per dyscyplina</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if (!empty($bestScores)): ?>
<div class="card shadow-sm mb-4 border-success">
    <div class="card-header bg-success text-white"><i class="bi bi-trophy-fill me-1"></i> Najlepsze wyniki per dyscyplina</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Dyscyplina</th><th>Najlepszy wynik</th></tr></thead>
            <tbody>
            <?php foreach ($bestScores as $b): ?>
                <tr>
                    <td><?= View::e(SnowboardResultModel::$DISCIPLINES[$b['discipline']] ?? $b['discipline']) ?></td>
                    <td class="font-monospace fw-bold text-success"><?= number_format((float)$b['max_score'], 2) ?></td>
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
                    <thead class="table-light"><tr><th>Data</th><th>Dyscyplina</th><th>Zawody</th><th>Best</th><th>#</th></tr></thead>
                    <tbody>
                    <?php foreach ($myResults as $r): ?>
                        <tr>
                            <td class="small"><?= View::e($r['event_date']) ?></td>
                            <td><small><?= View::e(SnowboardResultModel::$DISCIPLINES[$r['discipline']] ?? $r['discipline']) ?></small></td>
                            <td class="small"><?= View::e($r['event_name']) ?></td>
                            <td class="font-monospace fw-bold"><?= $r['best_score'] !== null ? number_format((float)$r['best_score'], 2) : '—' ?></td>
                            <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
