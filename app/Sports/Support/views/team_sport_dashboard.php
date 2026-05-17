<?php
use App\Helpers\View;
/**
 * Generic sport dashboard.
 * Scope vars:
 *   $sportLabel, $teams (with player_count),
 *   $topScorers (array of [first_name,last_name,goals,assists?,total_points?]),
 *   $recent (array of [home_team_name,away_team_name?,home_score,away_score]).
 */
?>
<h4 class="mb-3"><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard — <?= View::e($sportLabel) ?></h4>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header"><strong><i class="bi bi-people"></i> Drużyny (<?= count($teams ?? []) ?>)</strong></div>
            <ul class="list-group list-group-flush">
                <?php foreach (($teams ?? []) as $t): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= View::e($t['name']) ?></span>
                        <?php if (isset($t['player_count'])): ?>
                            <span class="badge bg-secondary"><?= (int)$t['player_count'] ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($teams)): ?><li class="list-group-item text-muted">Brak drużyn.</li><?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header"><strong><i class="bi bi-trophy"></i> Ranking</strong></div>
            <ul class="list-group list-group-flush">
                <?php foreach (($topScorers ?? []) as $i => $s): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= $i+1 ?>. <?= View::e($s['first_name'] . ' ' . $s['last_name']) ?>
                              <?php if (!empty($s['team_name'])): ?><small class="text-muted">(<?= View::e($s['team_name']) ?>)</small><?php endif; ?>
                        </span>
                        <span>
                            <?php if (isset($s['total_points'])): ?>
                                <span class="badge bg-success"><?= (int)$s['total_points'] ?> pkt</span>
                            <?php elseif (isset($s['goals'])): ?>
                                <span class="badge bg-success"><?= (int)$s['goals'] ?> G</span>
                                <?php if (isset($s['assists'])): ?>
                                    <span class="badge bg-info"><?= (int)$s['assists'] ?> A</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($topScorers)): ?><li class="list-group-item text-muted">Brak danych statystycznych.</li><?php endif; ?>
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
                        <span class="font-monospace fw-bold"><?= (int)($m['home_score'] ?? 0) ?> : <?= (int)($m['away_score'] ?? 0) ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($recent)): ?><li class="list-group-item text-muted">Brak meczów.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>
