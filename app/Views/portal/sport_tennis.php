<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-bullseye text-primary me-2"></i>Tenis ziemny</h3>
        <p class="text-muted mb-0">Mój ranking i historia meczów</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<!-- Stats cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Pozycja</div>
                <h3 class="mb-0">
                    <?php if ($rankingEntry): ?>
                        #<?= (int)$rankingEntry['position'] ?>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Punkty</div>
                <h3 class="mb-0 text-primary"><?= $rankingEntry ? (int)$rankingEntry['points'] : 0 ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Zwycięstwa</div>
                <h3 class="mb-0 text-success"><?= (int)($stats['wins'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Porażki</div>
                <h3 class="mb-0 text-danger"><?= (int)($stats['losses'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Match history -->
<div class="card shadow-sm">
    <div class="card-header">
        <i class="bi bi-clock-history me-1"></i> Moje mecze
    </div>
    <div class="card-body p-0">
        <?php if (empty($myMatches)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak meczów.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Przeciwnik</th><th>Sety</th><th>Wynik</th><th>Typ</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($myMatches as $m):
                        $isP1 = (int)$m['player1_id'] === (int)$member['id'];
                        $opponentName = $isP1 ? $m['p2_last'] . ' ' . $m['p2_first'] : $m['p1_last'] . ' ' . $m['p1_first'];
                        $won = (int)$m['winner_id'] === (int)$member['id'];
                        $noWinner = !$m['winner_id'];
                    ?>
                        <tr>
                            <td class="text-muted small"><?= View::e($m['match_date']) ?></td>
                            <td><?= View::e($opponentName) ?></td>
                            <td class="font-monospace"><?= View::e($m['sets']) ?></td>
                            <td>
                                <?php if ($noWinner): ?>
                                    <span class="badge bg-secondary">—</span>
                                <?php elseif ($won): ?>
                                    <span class="badge bg-success">Wygrana</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Porażka</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= View::e($m['match_type']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
