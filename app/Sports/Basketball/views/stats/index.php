<?php use App\Helpers\View; ?>
<div class="row g-3">
    <!-- Top Scorers -->
    <div class="col-md-4">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-trophy text-warning"></i> Punkty</h5>
            <?php if (empty($topScorers)): ?>
                <div class="text-muted small">Brak danych.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Zawodnik</th><th>Mecze</th><th>Suma</th><th>Śr.</th></tr></thead>
                    <tbody>
                    <?php foreach ($topScorers as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><?= (int)$p['games'] ?></td>
                            <td><strong><?= (int)$p['total_points'] ?></strong></td>
                            <td><?= View::e($p['avg_points']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Assists -->
    <div class="col-md-4">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-arrow-right-circle text-info"></i> Asysty</h5>
            <?php if (empty($topAssists)): ?>
                <div class="text-muted small">Brak danych.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Zawodnik</th><th>Mecze</th><th>Suma</th><th>Śr.</th></tr></thead>
                    <tbody>
                    <?php foreach ($topAssists as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><?= (int)$p['games'] ?></td>
                            <td><strong><?= (int)$p['total_assists'] ?></strong></td>
                            <td><?= View::e($p['avg_assists']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Rebounders -->
    <div class="col-md-4">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-arrow-repeat text-success"></i> Zbiórki</h5>
            <?php if (empty($topRebounders)): ?>
                <div class="text-muted small">Brak danych.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Zawodnik</th><th>Mecze</th><th>Suma</th><th>Śr.</th></tr></thead>
                    <tbody>
                    <?php foreach ($topRebounders as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><?= (int)$p['games'] ?></td>
                            <td><strong><?= (int)$p['total_rebounds'] ?></strong></td>
                            <td><?= View::e($p['avg_rebounds']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
