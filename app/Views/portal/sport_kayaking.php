<?php
use App\Helpers\View;
use App\Sports\Kayaking\Models\KayakResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-water text-primary me-2"></i>Kajakarstwo</h3>
        <p class="text-muted mb-0">Moje Personal Best i wyniki</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if (!empty($pbs)): ?>
<div class="card shadow-sm mb-4 border-success">
    <div class="card-header bg-success text-white"><i class="bi bi-trophy-fill me-1"></i> Moje Personal Best</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Dyscyplina</th><th>Dystans</th><th>Najlepszy czas</th></tr></thead>
            <tbody>
            <?php foreach ($pbs as $p): ?>
                <tr>
                    <td><?= View::e(KayakResultModel::$DISCIPLINES[$p['discipline']] ?? $p['discipline']) ?></td>
                    <td><?= (int)$p['distance_m'] ?> m</td>
                    <td class="font-monospace fw-bold text-success"><?= KayakResultModel::formatTime($p['best_time_ms']) ?></td>
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
                    <thead class="table-light"><tr><th>Data</th><th>Dyscyplina</th><th>Łódź</th><th>Dystans</th><th>Czas</th><th>#</th></tr></thead>
                    <tbody>
                    <?php foreach ($myResults as $r): ?>
                        <tr>
                            <td class="small"><?= View::e($r['event_date']) ?></td>
                            <td><small><?= View::e(KayakResultModel::$DISCIPLINES[$r['discipline']] ?? $r['discipline']) ?></small></td>
                            <td><small><?= View::e($r['boat_type'] ?? '—') ?></small></td>
                            <td class="font-monospace"><?= (int)$r['distance_m'] ?>m</td>
                            <td class="font-monospace"><?= KayakResultModel::formatTime($r['time_ms']) ?></td>
                            <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
