<?php use App\Helpers\View; ?>

<?php if (!empty($myTeam)): ?>
<div class="card p-3 mb-3">
    <h6 class="text-muted small mb-1"><i class="bi bi-people-fill me-1"></i>Moja drużyna</h6>
    <div class="fw-bold fs-5"><?= View::e($myTeam['team_name']) ?></div>
    <div class="text-muted small">
        Numer: <?= $myTeam['jersey_number'] ? '#' . (int)$myTeam['jersey_number'] : '—' ?>
        · Pozycja: <?= View::e($myTeam['position']) ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Nie jesteś przypisany/a do żadnej drużyny floorball.</div>
<?php endif; ?>

<?php if (!empty($stats)): ?>
<div class="card p-3 mb-3">
    <h6 class="text-muted small mb-2"><i class="bi bi-bar-chart me-1"></i>Statystyki sezonu</h6>
    <div class="row g-3 text-center">
        <div class="col-4">
            <div class="fs-3 fw-bold text-success"><?= (int)$stats['goals'] ?></div>
            <div class="text-muted small">Gole</div>
        </div>
        <div class="col-4">
            <div class="fs-3 fw-bold text-primary"><?= (int)$stats['assists'] ?></div>
            <div class="text-muted small">Asysty</div>
        </div>
        <div class="col-4">
            <div class="fs-3 fw-bold text-warning"><?= (int)$stats['pim'] ?></div>
            <div class="text-muted small">Kary</div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($upcomingMatches)): ?>
<div class="card p-3">
    <h6 class="mb-2"><i class="bi bi-calendar2-check me-1"></i>Nadchodzące mecze</h6>
    <?php foreach ($upcomingMatches as $m): ?>
        <div class="border-bottom py-2">
            <strong><?= View::e($m['home_team_name'] ?? '?') ?></strong>
            <span class="text-muted mx-1">vs</span>
            <strong><?= View::e($m['away_team_name'] ?? '?') ?></strong>
            <div class="small text-muted"><?= date('d.m.Y H:i', strtotime($m['match_date'])) ?>
                <?php if ($m['location']): ?> · <?= View::e($m['location']) ?><?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
