<?php
use App\Helpers\View;
/** @var string $sportKey */
/** @var array $manifest */
/** @var int $tournamentId */
/** @var array $rows */
/** @var array $attempts */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-bar-chart-fill text-primary me-2"></i>
        Live scoreboard — turniej #<?= (int)$tournamentId ?> (<?= View::e($manifest['name'] ?? $sportKey) ?>)
    </h3>
    <a href="<?= url('club/sport/' . $sportKey . '/attempts') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning">
        <i class="bi bi-trophy-fill me-1"></i> Klasyfikacja
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Zawodnik</th>
                    <th>Suma [kg]</th>
                    <th>Liczba podejść</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
                <tr class="<?= $i === 0 ? 'table-warning' : '' ?>">
                    <td><?= $i + 1 ?>.</td>
                    <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                    <td><strong><?= number_format((float)$r['total_kg'], 1) ?></strong></td>
                    <td><?= (int)$r['attempts'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">Brak podejść w turnieju.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        Wszystkie podejścia turnieju (<?= count($attempts) ?>)
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Czas</th>
                    <th>Zawodnik</th>
                    <th>Lift</th>
                    <th>Nr</th>
                    <th>Waga</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($attempts as $a): ?>
                <tr>
                    <td class="small"><?= View::e($a['attempted_at']) ?></td>
                    <td><?= View::e($a['last_name'] . ' ' . $a['first_name']) ?></td>
                    <td><?= View::e($a['lift_type']) ?></td>
                    <td><?= (int)$a['attempt_number'] ?></td>
                    <td><?= $a['weight_kg'] !== null ? number_format((float)$a['weight_kg'], 1) . ' kg' : '—' ?></td>
                    <td>
                        <?php if ((int)$a['success'] === 1): ?>
                            <span class="badge bg-success">OK</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">FAIL</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
