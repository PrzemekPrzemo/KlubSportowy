<?php
use App\Helpers\View;
use App\Sports\Biathlon\Models\BiathlonResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-bullseye text-primary me-2"></i>Biathlon</h3>
        <p class="text-muted mb-0">Moja skuteczność strzelania i wyniki</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm text-center border-success">
            <div class="card-body">
                <div class="text-muted small">Skuteczność strzelania</div>
                <h1 class="display-5 mb-0 text-success">
                    <?= $accuracy['accuracy_pct'] !== null ? $accuracy['accuracy_pct'] . '%' : '—' ?>
                </h1>
                <small class="text-muted mt-2 d-block">
                    <?= (int)$accuracy['total_shots'] - (int)$accuracy['total_misses'] ?>/<?= (int)$accuracy['total_shots'] ?> trafień
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Strzały łącznie</div>
                <h1 class="display-5 mb-0"><?= (int)$accuracy['total_shots'] ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Pudła łącznie</div>
                <h1 class="display-5 mb-0 text-danger"><?= (int)$accuracy['total_misses'] ?></h1>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Moje wyniki</div>
    <div class="card-body p-0">
        <?php if (empty($myResults)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wyników.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Format</th><th>Km</th><th>Strzelanie</th><th>Total</th><th>#</th></tr></thead>
                    <tbody>
                    <?php foreach ($myResults as $r): ?>
                        <tr>
                            <td class="small"><?= View::e($r['event_date']) ?></td>
                            <td><small><?= View::e(BiathlonResultModel::$FORMATS[$r['format']] ?? $r['format']) ?></small></td>
                            <td class="font-monospace"><?= number_format((float)$r['distance_km'], 1) ?></td>
                            <td class="small">
                                <?php if ($r['shootings_total']): ?>
                                    <?= (int)$r['shootings_total'] - (int)($r['misses_total'] ?? 0) ?>/<?= (int)$r['shootings_total'] ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="font-monospace"><?= BiathlonResultModel::formatTime($r['total_time_s']) ?></td>
                            <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
