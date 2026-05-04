<?php use App\Helpers\View; ?>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Mecze</div>
            <div class="h3 mb-0"><?= (int)($summary['matches_finished'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Gole</div>
            <div class="h3 mb-0 text-success"><?= (int)($summary['goals'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Asysty</div>
            <div class="h3 mb-0 text-info"><?= (int)($summary['assists'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Kartki Ż / C</div>
            <div class="h3 mb-0">
                <span class="text-warning"><?= (int)($summary['yellow_cards'] ?? 0) ?></span>
                /
                <span class="text-danger"><?= (int)($summary['red_cards'] ?? 0) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-trophy text-success"></i> Strzelcy</h5>
            <?php if (empty($topScorers)): ?>
                <div class="text-muted small">Brak danych.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Zawodnik</th><th>Mecze</th><th>Gole</th></tr></thead>
                    <tbody>
                    <?php foreach ($topScorers as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><?= (int)$p['games'] ?></td>
                            <td><strong><?= (int)$p['total_goals'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-arrow-right-circle text-info"></i> Asysty</h5>
            <?php if (empty($topAssists)): ?>
                <div class="text-muted small">Brak danych.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Zawodnik</th><th>Mecze</th><th>Asysty</th></tr></thead>
                    <tbody>
                    <?php foreach ($topAssists as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><?= (int)$p['games'] ?></td>
                            <td><strong><?= (int)$p['total_assists'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-square-fill text-warning"></i> Żółte kartki</h5>
            <?php if (empty($topYellow)): ?>
                <div class="text-muted small">Brak danych.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Zawodnik</th><th>Mecze</th><th>Sztuk</th></tr></thead>
                    <tbody>
                    <?php foreach ($topYellow as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><?= (int)$p['games'] ?></td>
                            <td><strong><?= (int)$p['total_yellow'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-square-fill text-danger"></i> Czerwone kartki</h5>
            <?php if (empty($topRed)): ?>
                <div class="text-muted small">Brak danych.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Zawodnik</th><th>Mecze</th><th>Sztuk</th></tr></thead>
                    <tbody>
                    <?php foreach ($topRed as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><?= (int)$p['games'] ?></td>
                            <td><strong><?= (int)$p['total_red'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
