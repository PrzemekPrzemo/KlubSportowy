<?php use App\Helpers\View; ?>

<div class="pub-hero mb-4" <?php if (!empty($club['primary_color'])): ?>
    style="background: linear-gradient(135deg, <?= View::e($club['primary_color']) ?> 0%, #212529 100%);"
<?php endif; ?>>
    <div class="container">
        <h1><?= View::e($club['name']) ?></h1>
        <p class="mb-0 opacity-75">Wyniki i zakonczone wydarzenia</p>
    </div>
</div>

<div class="container pb-5">
    <div class="mb-3">
        <a href="<?= url('pub/' . urlencode($club['subdomain'])) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Powrot do strony klubu
        </a>
    </div>

    <?php if (empty($results)): ?>
        <div class="alert alert-info">Brak zakonczonych wydarzen.</div>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Nazwa</th>
                            <th>Typ</th>
                            <th>Sport</th>
                            <th>Miejsce</th>
                            <th>Wynik</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                            <tr>
                                <td><small><?= format_date($r['event_date']) ?></small></td>
                                <td><strong><?= View::e($r['name']) ?></strong></td>
                                <td><span class="badge bg-info"><?= View::e($r['type']) ?></span></td>
                                <td>
                                    <?php if (!empty($r['sport_name'])): ?>
                                        <span class="sport-badge" style="background: <?= View::e($r['sport_color'] ?? '#6c757d') ?>">
                                            <?= View::e($r['sport_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= View::e($r['location'] ?? '') ?></td>
                                <td>
                                    <?php if ($r['home_score'] !== null && $r['away_score'] !== null): ?>
                                        <strong><?= View::e($r['home_team_name'] ?? 'Gospodarze') ?></strong>
                                        <?= (int)$r['home_score'] ?> : <?= (int)$r['away_score'] ?>
                                        <strong><?= View::e($r['away_team_name'] ?? 'Goscie') ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= View::e($r['status']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
