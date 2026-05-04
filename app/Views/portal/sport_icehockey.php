<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-snow text-primary me-2"></i>Hokej na lodzie</h3>
        <p class="text-muted mb-0">Moja drużyna i statystyki</p>
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
                <span class="badge bg-secondary">Chwyt: <?= View::e($myTeam['shoots']) ?></span>
                <?php if ($myTeam['is_captain']): ?><span class="badge bg-warning text-dark">C (Kapitan)</span><?php endif; ?>
                <?php if ($myTeam['is_assistant']): ?><span class="badge bg-info">A (Asystent)</span><?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">Nie jesteś przypisany/a do drużyny.</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center"><div class="card-body"><div class="text-muted small">Gole (G)</div><h2 class="mb-0 text-success"><?= (int)($myStats['goals'] ?? 0) ?></h2></div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center"><div class="card-body"><div class="text-muted small">Asysty (A)</div><h2 class="mb-0 text-primary"><?= (int)($myStats['assists'] ?? 0) ?></h2></div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center"><div class="card-body"><div class="text-muted small">Punkty (P)</div><h2 class="mb-0"><?= (int)($myStats['points'] ?? 0) ?></h2></div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center"><div class="card-body"><div class="text-muted small">PIM (min kar)</div><h2 class="mb-0 text-warning"><?= (int)($myStats['pim'] ?? 0) ?></h2></div></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Ostatnie mecze drużyny</div>
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
                            <td class="font-monospace"><?= (int)$m['total_home'] ?>:<?= (int)$m['total_away'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
