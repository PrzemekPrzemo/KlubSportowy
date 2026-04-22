<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-suit-spade-fill text-primary me-2"></i>Brydż sportowy</h3>
        <p class="text-muted mb-0">Moje pary, turnieje i punkty PZBS</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<div class="card shadow-sm mb-4 border-primary">
    <div class="card-body text-center">
        <div class="text-muted small">Moje punkty rankingowe PZBS</div>
        <h1 class="display-3 mb-0 text-primary"><?= number_format((float)$totalPzbs, 2) ?></h1>
    </div>
</div>

<?php if (!empty($myPartnerships)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-people me-1"></i> Moje pary</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Partner</th><th>Nazwa</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($myPartnerships as $p):
                $partner = (int)$p['player1_id'] === (int)$member['id']
                    ? $p['p2_last'] . ' ' . $p['p2_first']
                    : $p['p1_last'] . ' ' . $p['p1_first'];
            ?>
                <tr>
                    <td><strong><?= View::e($partner) ?></strong></td>
                    <td><?= View::e($p['name'] ?? '—') ?></td>
                    <td>
                        <?php if ($p['active']): ?>
                            <span class="badge bg-success">Aktywna</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Nieaktywna</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header">Moje turnieje</div>
    <div class="card-body p-0">
        <?php if (empty($myTournaments)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak turniejów.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Turniej</th><th>#</th><th>MP</th><th>IMP</th><th>PZBS</th></tr></thead>
                    <tbody>
                    <?php foreach ($myTournaments as $t): ?>
                        <tr>
                            <td class="small"><?= View::e($t['tournament_date']) ?></td>
                            <td class="small"><?= View::e($t['name']) ?></td>
                            <td><?php if ($t['place']): ?><span class="badge bg-primary">#<?= (int)$t['place'] ?></span><?php endif; ?></td>
                            <td class="small font-monospace"><?= $t['score_mp']  !== null ? number_format((float)$t['score_mp'], 2)  : '—' ?></td>
                            <td class="small font-monospace"><?= $t['score_imp'] !== null ? number_format((float)$t['score_imp'], 2) : '—' ?></td>
                            <td class="small font-monospace fw-bold"><?= $t['pzbs_points'] !== null ? number_format((float)$t['pzbs_points'], 2) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
