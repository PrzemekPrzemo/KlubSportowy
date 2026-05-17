<?php use App\Helpers\View; ?>

<?php if (!empty($myTeam)): ?>
<div class="card p-3 mb-3">
    <h6 class="text-muted small mb-1"><i class="bi bi-snow me-1"></i>Moja drużyna</h6>
    <div class="fw-bold fs-5"><?= View::e($myTeam['team_name']) ?></div>
    <div class="text-muted small">
        Pozycja: <span class="badge bg-info text-dark"><?= View::e($myTeam['position']) ?></span>
        <?php if (!empty($myTeam['is_captain'])): ?><span class="badge bg-warning text-dark ms-1">Skip/Kapitan</span><?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Nie jesteś przypisany/a do drużyny curlingu.</div>
<?php endif; ?>

<?php if (!empty($myStats)): ?>
<div class="card p-3 mb-3">
    <h6 class="text-muted small mb-2"><i class="bi bi-bar-chart me-1"></i>Statystyki sezonu</h6>
    <div class="row g-3 text-center">
        <div class="col-md-3 col-6"><div class="fs-3 fw-bold text-primary"><?= (int)($myStats['matches_played'] ?? 0) ?></div><div class="text-muted small">Mecze</div></div>
        <div class="col-md-3 col-6"><div class="fs-3 fw-bold text-success"><?= (int)($myStats['points_scored'] ?? 0) ?></div><div class="text-muted small">Punkty zdobyte</div></div>
        <div class="col-md-3 col-6"><div class="fs-3 fw-bold text-danger"><?= (int)($myStats['points_against'] ?? 0) ?></div><div class="text-muted small">Punkty stracone</div></div>
        <div class="col-md-3 col-6"><div class="fs-3 fw-bold text-info"><?= (int)($myStats['ends_played'] ?? 0) ?></div><div class="text-muted small">Endy</div></div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($recentMatches)): ?>
<div class="card p-3">
    <h6 class="mb-2"><i class="bi bi-calendar2-check me-1"></i>Ostatnie mecze</h6>
    <?php foreach ($recentMatches as $m): ?>
        <div class="border-bottom py-2">
            <strong><?= View::e($m['home_team_name'] ?? '?') ?></strong>
            <span class="text-muted mx-1">vs</span>
            <strong><?= View::e($m['away_team_name'] ?? '?') ?></strong>
            <span class="font-monospace fw-bold ms-2"><?= (int)$m['home_score'] ?>:<?= (int)$m['away_score'] ?></span>
            <div class="small text-muted"><?= date('d.m.Y H:i', strtotime($m['match_date'])) ?></div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
