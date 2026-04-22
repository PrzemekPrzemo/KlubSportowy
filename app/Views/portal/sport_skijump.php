<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-arrow-up-right-circle text-primary me-2"></i>Skoki narciarskie</h3>
        <p class="text-muted mb-0">Moje wyniki i rekord długości skoku</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if (!empty($longest)): ?>
<div class="card shadow-sm mb-4 border-success">
    <div class="card-body text-center">
        <div class="text-muted small">Mój najdłuższy skok</div>
        <h1 class="display-4 mb-0 text-success"><?= number_format((float)$longest['longest_m'], 1) ?> m</h1>
        <div class="text-muted small mt-2">
            <?= View::e($longest['event_name']) ?> — <?= View::e($longest['event_date']) ?>
            <?php if ($longest['hill_k']): ?>(K<?= (int)$longest['hill_k'] ?>)<?php endif; ?>
        </div>
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
                    <thead class="table-light"><tr><th>Data</th><th>Skocznia</th><th>Skok 1</th><th>Skok 2</th><th>Total pkt</th><th>#</th></tr></thead>
                    <tbody>
                    <?php foreach ($myResults as $r): ?>
                        <tr>
                            <td class="small"><?= View::e($r['event_date']) ?></td>
                            <td class="small"><?= View::e($r['venue'] ?? $r['event_name']) ?> <?php if ($r['hill_k']): ?>(K<?= (int)$r['hill_k'] ?>)<?php endif; ?></td>
                            <td class="font-monospace"><?= $r['jump1_m'] ? number_format((float)$r['jump1_m'], 1) . 'm' : '—' ?></td>
                            <td class="font-monospace"><?= $r['jump2_m'] ? number_format((float)$r['jump2_m'], 1) . 'm' : '—' ?></td>
                            <td class="font-monospace fw-bold"><?= $r['total_points'] !== null ? number_format((float)$r['total_points'], 2) : '—' ?></td>
                            <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
