<?php
use App\Helpers\View;
use App\Sports\XcSki\Models\XcSkiResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-stopwatch text-primary me-2"></i>Narciarstwo biegowe</h3>
        <p class="text-muted mb-0">Moje wyniki i FIS points</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if (!empty($bestFis)): ?>
<div class="card shadow-sm mb-4 border-primary">
    <div class="card-header bg-primary text-white"><i class="bi bi-trophy-fill me-1"></i> Najlepsze FIS per styl</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Styl</th><th>FIS pkt</th></tr></thead>
            <tbody>
            <?php foreach ($bestFis as $f): ?>
                <tr>
                    <td><?= View::e(XcSkiResultModel::$TECHNIQUES[$f['technique']] ?? $f['technique']) ?></td>
                    <td class="font-monospace fw-bold text-success"><?= number_format((float)$f['best_fis'], 2) ?></td>
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
                    <thead class="table-light"><tr><th>Data</th><th>Styl</th><th>Km</th><th>Zawody</th><th>Czas</th><th>#</th><th>FIS</th></tr></thead>
                    <tbody>
                    <?php foreach ($myResults as $r): ?>
                        <tr>
                            <td class="small"><?= View::e($r['event_date']) ?></td>
                            <td><small><?= View::e(XcSkiResultModel::$TECHNIQUES[$r['technique']] ?? $r['technique']) ?></small></td>
                            <td class="font-monospace"><?= number_format((float)$r['distance_km'], 1) ?></td>
                            <td class="small"><?= View::e($r['event_name']) ?></td>
                            <td class="font-monospace">
                                <?php if ($r['dnf']): ?>DNF<?php elseif ($r['dns']): ?>DNS<?php else: ?><?= XcSkiResultModel::formatTime($r['time_s']) ?><?php endif; ?>
                            </td>
                            <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                            <td class="small"><?= $r['fis_points'] !== null ? number_format((float)$r['fis_points'], 2) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
