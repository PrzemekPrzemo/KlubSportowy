<?php use App\Helpers\View; ?>

<?php if (!empty($bestScores)): ?>
<div class="card p-3 mb-3">
    <h6 class="mb-3"><i class="bi bi-bar-chart me-1"></i>Najlepsze wyniki per dyscyplina</h6>
    <div class="row g-3">
        <?php foreach ($bestScores as $disc => $score): ?>
        <div class="col-sm-6 col-md-3">
            <div class="border rounded p-2 text-center">
                <div class="text-muted small"><?= ucfirst(View::e($disc)) ?></div>
                <div class="fs-4 fw-bold"><?= number_format((float)$score['total_score'], 3) ?></div>
                <div class="small text-muted"><?= View::e($score['event_name']) ?></div>
                <div class="small text-muted"><?= View::e($score['event_date']) ?></div>
                <?php if ($score['placement']): ?><div class="badge bg-warning text-dark">#<?= (int)$score['placement'] ?></div><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Brak wyników gimnastycznych.</div>
<?php endif; ?>

<?php if (!empty($minorConsent)): ?>
<div class="card p-3 mb-3">
    <h6 class="mb-2"><i class="bi bi-shield-check me-1"></i>Status zgód rodzicielskich</h6>
    <div class="d-flex gap-3">
        <div>
            <i class="bi bi-<?= $minorConsent['photo_consent'] ? 'check-circle-fill text-success' : 'x-circle text-danger' ?>"></i>
            Zgoda na zdjęcia
        </div>
        <div>
            <i class="bi bi-<?= $minorConsent['media_consent'] ? 'check-circle-fill text-success' : 'x-circle text-danger' ?>"></i>
            Zgoda na media
        </div>
    </div>
    <?php if (!$minorConsent['photo_consent'] || !$minorConsent['media_consent']): ?>
        <div class="alert alert-warning mt-2 mb-0 py-1 small">
            <i class="bi bi-exclamation-triangle me-1"></i>Niekompletne zgody — skontaktuj się z trenerem.
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($recentResults)): ?>
<div class="card p-3">
    <h6 class="mb-3"><i class="bi bi-clock-history me-1"></i>Ostatnie wyniki</h6>
    <?php foreach ($recentResults as $r): ?>
        <div class="border-bottom py-2 d-flex justify-content-between align-items-center">
            <div>
                <strong><?= View::e($r['event_name']) ?></strong>
                <div class="small text-muted"><?= ucfirst($r['discipline']) ?><?= $r['apparatus'] ? ' · ' . View::e($r['apparatus']) : '' ?> · <?= View::e($r['event_date']) ?></div>
            </div>
            <div class="text-end">
                <div class="fw-bold"><?= number_format((float)$r['total_score'], 3) ?></div>
                <?php if ($r['placement']): ?><small class="text-muted">#<?= (int)$r['placement'] ?></small><?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
