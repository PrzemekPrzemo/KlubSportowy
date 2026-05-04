<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-circle-half text-primary me-2"></i>Golf</h3>
        <p class="text-muted mb-0">Mój handicap WHS i historia rund</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-primary">
            <div class="card-body text-center">
                <div class="text-muted small"><i class="bi bi-graph-up-arrow"></i> Aktualny WHS Index</div>
                <h1 class="display-3 mb-0 text-primary">
                    <?= $currentHandicap ? number_format((float)$currentHandicap['whs_index'], 1) : '—' ?>
                </h1>
                <?php if ($currentHandicap): ?>
                    <small class="text-muted">Aktualizacja: <?= View::e($currentHandicap['updated_at']) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-success">
            <div class="card-body text-center">
                <div class="text-muted small"><i class="bi bi-trophy-fill"></i> Najlepszy net score</div>
                <?php if ($bestRound): ?>
                    <h1 class="display-3 mb-0 text-success"><?= number_format((float)$bestRound['net_score'], 1) ?></h1>
                    <small class="text-muted"><?= View::e($bestRound['course_name']) ?> — <?= View::e($bestRound['round_date']) ?></small>
                <?php else: ?>
                    <h1 class="display-3 mb-0 text-muted">—</h1>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Moje rundy</div>
    <div class="card-body p-0">
        <?php if (empty($myRounds)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak rund.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Kurs</th><th>Dołki</th><th>Tees</th><th>Uderzenia</th><th>Net</th></tr></thead>
                    <tbody>
                    <?php foreach ($myRounds as $r): ?>
                        <tr>
                            <td class="small"><?= View::e($r['round_date']) ?></td>
                            <td class="small"><?= View::e($r['course_name']) ?></td>
                            <td><?= (int)$r['holes'] ?></td>
                            <td><small><?= View::e($r['tees']) ?></small></td>
                            <td class="font-monospace"><?= $r['total_strokes'] ?? '—' ?></td>
                            <td class="font-monospace fw-bold"><?= $r['net_score'] !== null ? number_format((float)$r['net_score'], 1) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
