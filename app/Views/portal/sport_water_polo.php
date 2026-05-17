<?php use App\Helpers\View; ?>

<?php if (!empty($myTeam)): ?>
<div class="card p-3 mb-3">
    <h6 class="text-muted small mb-1"><i class="bi bi-droplet-half me-1"></i>Moja drużyna</h6>
    <div class="fw-bold fs-5"><?= View::e($myTeam['team_name']) ?></div>
    <div class="text-muted small">
        Czepek: <?= $myTeam['cap_number'] ? '#' . (int)$myTeam['cap_number'] : '—' ?>
        · Pozycja: <?= View::e($myTeam['position']) ?>
        <?php if (!empty($myTeam['is_captain'])): ?><span class="badge bg-warning text-dark ms-1">C</span><?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Nie jesteś przypisany/a do drużyny piłki wodnej.</div>
<?php endif; ?>

<?php if (!empty($myStats)): ?>
<div class="card p-3 mb-3">
    <h6 class="text-muted small mb-2"><i class="bi bi-bar-chart me-1"></i>Moje statystyki sezonu</h6>
    <div class="row g-3 text-center">
        <div class="col-md-2 col-6"><div class="fs-3 fw-bold text-success"><?= (int)($myStats['goals'] ?? 0) ?></div><div class="text-muted small">Gole</div></div>
        <div class="col-md-2 col-6"><div class="fs-3 fw-bold text-primary"><?= (int)($myStats['assists'] ?? 0) ?></div><div class="text-muted small">Asysty</div></div>
        <div class="col-md-2 col-6"><div class="fs-3 fw-bold" style="color:#ffc107"><?= (int)($myStats['exclusions'] ?? 0) ?></div><div class="text-muted small">Wykluczenia</div></div>
        <div class="col-md-2 col-6"><div class="fs-3 fw-bold text-danger"><?= (int)($myStats['exclusions_5'] ?? 0) ?></div><div class="text-muted small">5 fauli</div></div>
        <div class="col-md-2 col-6"><div class="fs-3 fw-bold text-info"><?= (int)($myStats['saves'] ?? 0) ?></div><div class="text-muted small">Obrony</div></div>
        <div class="col-md-2 col-6"><div class="fs-3 fw-bold text-secondary"><?= (int)($myStats['penalties'] ?? 0) ?></div><div class="text-muted small">Karne</div></div>
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
