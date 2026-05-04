<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Piłka ręczna</h3>
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
            <div class="d-flex gap-3 align-items-center">
                <?php if ($myTeam['jersey_number']): ?>
                    <span class="badge bg-dark fs-5">#<?= (int)$myTeam['jersey_number'] ?></span>
                <?php endif; ?>
                <span class="badge bg-secondary">Pozycja: <?= View::e($myTeam['position']) ?></span>
                <?php if ($myTeam['is_captain']): ?>
                    <span class="badge bg-warning text-dark">Kapitan</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">Nie jesteś przypisany/a do żadnej drużyny.</div>
<?php endif; ?>

<!-- Stats cards -->
<div class="row g-3 mb-4">
    <div class="col-md-2 col-4">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">Gole</div>
                <h3 class="mb-0 text-success"><?= (int)($myStats['goals'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-4">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">Asysty</div>
                <h3 class="mb-0 text-primary"><?= (int)($myStats['assists'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-4">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">7m +/-</div>
                <h3 class="mb-0"><?= (int)($myStats['seven_m_scored'] ?? 0) ?>/<?= (int)($myStats['seven_m_scored'] ?? 0) + (int)($myStats['seven_m_missed'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-4">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">2 min</div>
                <h3 class="mb-0 text-warning"><?= (int)($myStats['two_min'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-4">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">Żółte</div>
                <h3 class="mb-0" style="color:#ffc107"><?= (int)($myStats['yellow_cards'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-4">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">Czerwone</div>
                <h3 class="mb-0 text-danger"><?= (int)($myStats['red_cards'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming matches -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-calendar3 me-1"></i> Nadchodzące mecze</div>
    <div class="card-body p-0">
        <?php if (empty($upcoming)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak zaplanowanych meczów.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Gospodarze</th><th>Goście</th><th>Miejsce</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $m): ?>
                            <tr>
                                <td class="small"><?= date('Y-m-d H:i', strtotime($m['match_date'])) ?></td>
                                <td><strong><?= View::e($m['home_team_name']) ?></strong></td>
                                <td><?= View::e($m['away_team_name'] ?? '—') ?></td>
                                <td class="small text-muted"><?= View::e($m['location'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
