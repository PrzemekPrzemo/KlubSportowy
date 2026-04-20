<?php use App\Helpers\View; ?>

<!-- PR Board -->
<div class="card p-3 mb-3">
    <h6 class="mb-3"><i class="bi bi-bar-chart-line me-1"></i>Moje Personal Records</h6>
    <?php if (empty($topPrs)): ?>
        <div class="text-muted small">Brak rekordów.</div>
    <?php else: ?>
        <div class="row g-2">
            <?php
            $unitLabels = ['kg'=>'kg','lb'=>'lb','reps'=>'reps','time'=>'','m'=>'m','cal'=>'cal'];
            foreach ($topPrs as $pr): ?>
            <div class="col-6">
                <div class="bg-light rounded p-2 text-center">
                    <div class="small text-muted text-truncate"><?= View::e($pr['movement']) ?></div>
                    <div class="fw-bold text-primary">
                        <?= View::e($pr['pr_value']) ?>
                        <small class="text-muted"><?= $unitLabels[$pr['unit']] ?? $pr['unit'] ?></small>
                    </div>
                    <div class="small text-muted"><?= View::e($pr['pr_date']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Recent WOD Scores -->
<div class="card p-3 mb-3">
    <h6 class="mb-3"><i class="bi bi-lightning-charge me-1"></i>Ostatnie WOD</h6>
    <?php if (empty($recentScores)): ?>
        <div class="text-muted small">Brak wyników WOD.</div>
    <?php else: ?>
        <?php foreach ($recentScores as $s): ?>
            <div class="border-bottom py-2 d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= View::e($s['wod_name']) ?></strong>
                    <div class="small text-muted"><?= View::e($s['score_date']) ?></div>
                </div>
                <div class="text-end">
                    <div class="fw-bold"><?= View::e($s['score']) ?></div>
                    <div>
                        <?php if ($s['rx']): ?><span class="badge bg-success" style="font-size:.65rem">RX</span><?php endif; ?>
                        <?php if ($s['scaled']): ?><span class="badge bg-warning text-dark" style="font-size:.65rem">Scaled</span><?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Leaderboard position hint -->
<?php if (!empty($leaderboardPositions)): ?>
<div class="card p-3">
    <h6 class="mb-3"><i class="bi bi-trophy me-1"></i>Pozycja w leaderboardzie</h6>
    <?php foreach ($leaderboardPositions as $entry): ?>
        <div class="d-flex justify-content-between small py-1 border-bottom">
            <span><?= View::e($entry['wod_name']) ?></span>
            <span>
                <?php if ($entry['position']): ?>
                    <strong class="text-primary">#<?= (int)$entry['position'] ?></strong>
                    <span class="text-muted"> (<?= View::e($entry['score']) ?>)</span>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </span>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
