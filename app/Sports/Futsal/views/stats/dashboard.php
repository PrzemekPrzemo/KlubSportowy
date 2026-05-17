<?php use App\Helpers\View; ?>

<h4 class="mb-3"><i class="bi bi-speedometer2 text-success me-2"></i>Dashboard — <?= View::e($sportLabel) ?></h4>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header"><strong><i class="bi bi-people"></i> Drużyny (<?= count($teams) ?>)</strong></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($teams as $t): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= View::e($t['name']) ?></span>
                        <span class="badge bg-secondary"><?= (int)$t['player_count'] ?> zawodników</span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($teams)): ?><li class="list-group-item text-muted">Brak drużyn.</li><?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header"><strong><i class="bi bi-trophy"></i> Ranking strzelców</strong></div>
            <ul class="list-group list-group-flush">
                <?php foreach (($topScorers ?? []) as $i => $s): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= $i+1 ?>. <?= View::e($s['first_name'] . ' ' . $s['last_name']) ?></span>
                        <span><span class="badge bg-success"><?= (int)$s['goals'] ?> G</span>
                              <span class="badge bg-info"><?= (int)($s['assists'] ?? 0) ?> A</span></span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($topScorers)): ?><li class="list-group-item text-muted">Brak danych — wpisz pierwsze zdarzenia meczowe.</li><?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header"><strong><i class="bi bi-calendar-event"></i> Ostatnie mecze</strong></div>
            <ul class="list-group list-group-flush small">
                <?php foreach (($recent ?? []) as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= View::e($m['home_team_name'] ?? '?') ?> vs <?= View::e($m['away_team_name'] ?? '?') ?></span>
                        <span class="font-monospace fw-bold"><?= (int)$m['home_score'] ?> : <?= (int)$m['away_score'] ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($recent)): ?><li class="list-group-item text-muted">Brak meczów.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>
