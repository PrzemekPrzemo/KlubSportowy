<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-bar-chart-steps text-primary me-2"></i>Podnoszenie ciężarów</h3>
        <p class="text-muted mb-0">Moje rekordy i wyniki zawodów</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<!-- Personal bests per weight class -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white"><i class="bi bi-trophy-fill me-1"></i> Moje rekordy osobiste (PB) per kategoria wagowa</div>
    <div class="card-body p-0">
        <?php if (empty($personalBests)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wyników. Zapisz się na pierwsze zawody.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Kategoria</th><th class="text-center text-warning">Rwanie</th><th class="text-center text-danger">Podrzut</th><th class="text-center">Dwubój</th><th class="text-center">Sinclair</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($personalBests as $pb): ?>
                            <tr>
                                <td><span class="badge bg-dark"><?= View::e($pb['weight_class']) ?> kg</span></td>
                                <td class="text-center font-monospace fw-bold">
                                    <?= $pb['best_snatch'] ? number_format((float)$pb['best_snatch'], 1) . ' kg' : '—' ?>
                                </td>
                                <td class="text-center font-monospace fw-bold">
                                    <?= $pb['best_cj'] ? number_format((float)$pb['best_cj'], 1) . ' kg' : '—' ?>
                                </td>
                                <td class="text-center font-monospace fw-bold text-success">
                                    <?= $pb['best_total'] ? number_format((float)$pb['best_total'], 1) . ' kg' : '—' ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($pb['best_sinclair']): ?>
                                        <span class="badge bg-primary"><?= number_format((float)$pb['best_sinclair'], 2) ?></span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Competition history -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-clock-history me-1"></i> Historia startów</div>
    <div class="card-body p-0">
        <?php if (empty($myResults)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak startów.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Zawody</th><th>Kat.</th><th>Waga</th><th>Rwanie</th><th>Podrzut</th><th>Dwubój</th><th>Sinclair</th><th>#</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myResults as $r): ?>
                            <tr>
                                <td class="small"><?= View::e($r['competition_date']) ?></td>
                                <td><?= View::e($r['competition_name']) ?></td>
                                <td><?= View::e($r['weight_class']) ?></td>
                                <td class="small"><?= $r['body_weight'] ? View::e($r['body_weight']) : '—' ?></td>
                                <td class="font-monospace"><?= $r['snatch_best'] ? number_format((float)$r['snatch_best'], 1) : '—' ?></td>
                                <td class="font-monospace"><?= $r['cleanjerk_best'] ? number_format((float)$r['cleanjerk_best'], 1) : '—' ?></td>
                                <td class="font-monospace fw-bold"><?= $r['total'] ? number_format((float)$r['total'], 1) : '—' ?></td>
                                <td class="small"><?= $r['sinclair_coeff'] ? number_format((float)$r['sinclair_coeff'], 2) : '—' ?></td>
                                <td><?php if ($r['placement']): ?><span class="badge bg-primary">#<?= (int)$r['placement'] ?></span><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
