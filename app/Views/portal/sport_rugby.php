<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-trophy text-primary me-2"></i>Rugby</h3>
        <p class="text-muted mb-0">Moja drużyna i statystyki sezonu</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if ($myTeam): ?>
<div class="card shadow-sm mb-4 border-primary">
    <div class="card-body">
        <h5 class="card-title"><?= View::e($myTeam['name']) ?></h5>
        <div class="d-flex gap-3 flex-wrap">
            <?php if ($myTeam['jersey_number']): ?>
                <span class="badge bg-dark fs-5">#<?= (int)$myTeam['jersey_number'] ?></span>
            <?php endif; ?>
            <span class="badge bg-primary">Pozycja: <?= View::e($myTeam['position']) ?></span>
            <span class="badge bg-secondary"><?= View::e($myTeam['format']) ?></span>
            <?php if ($myTeam['is_captain']): ?><span class="badge bg-warning text-dark">Kapitan</span><?php endif; ?>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-info">Nie jesteś przypisany/a do drużyny rugby w tym klubie.</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body p-2"><div class="text-muted small">Przyłożenia</div><h3 class="mb-0 text-success"><?= (int)($myStats['tries'] ?? 0) ?></h3></div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body p-2"><div class="text-muted small">Punkty</div><h3 class="mb-0 text-primary"><?= (int)($myStats['total_points'] ?? 0) ?></h3></div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body p-2"><div class="text-muted small">Żółte kartki</div><h3 class="mb-0" style="color:#ffc107"><?= (int)($myStats['yellow_cards'] ?? 0) ?></h3></div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body p-2"><div class="text-muted small">Czerwone</div><h3 class="mb-0 text-danger"><?= (int)($myStats['red_cards'] ?? 0) ?></h3></div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-calendar3 me-1"></i> Ostatnie mecze drużyny</div>
    <div class="card-body p-0">
        <?php if (empty($recentMatches)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak meczów.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Gospodarze</th><th>Goście</th><th>Wynik</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentMatches as $m): ?>
                        <tr>
                            <td class="small"><?= date('Y-m-d', strtotime($m['match_date'])) ?></td>
                            <td><strong><?= View::e($m['home_team_name']) ?></strong></td>
                            <td><?= View::e($m['away_team_name'] ?? '—') ?></td>
                            <td class="font-monospace"><?= (int)$m['home_score'] ?>:<?= (int)$m['away_score'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
