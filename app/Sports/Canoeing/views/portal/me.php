<?php use App\Helpers\View;
use App\Sports\Canoeing\Models\CanoeingRaceResultModel; ?>

<h4 class="mb-3"><i class="bi bi-water text-primary me-2"></i>Moje kajakarstwo</h4>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h6 class="mb-2"><i class="bi bi-person-badge me-1"></i>Moj profil</h6>
        <?php if ($profile): ?>
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted">Klasa lodzi:</small>
                    <div><strong><?= View::e($boatClasses[$profile['boat_class']] ?? $profile['boat_class']) ?></strong></div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Klasa wagowa:</small>
                    <div><strong><?= View::e($profile['weight_class'] ?? '—') ?></strong></div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Ranking krajowy:</small>
                    <div><strong><?= $profile['national_rank'] !== null ? (int)$profile['national_rank'] : '—' ?></strong></div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-muted">Brak profilu — administrator klubu wypelni dane.</div>
        <?php endif; ?>
    </div>
</div>

<h5 class="mb-2"><i class="bi bi-stopwatch me-1"></i>Moje wyniki</h5>
<?php if (empty($results)): ?>
    <div class="text-muted small">Brak wynikow wyscigow.</div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Turniej</th>
                        <th>Data</th>
                        <th class="text-center">Dystans</th>
                        <th class="text-center">Lodz</th>
                        <th class="text-end">Czas</th>
                        <th class="text-end">Kary</th>
                        <th class="text-center">Pozycja</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $r): ?>
                    <tr>
                        <td><?= View::e($r['tournament_name']) ?></td>
                        <td class="small text-muted"><?= View::e($r['tournament_date']) ?></td>
                        <td class="text-center"><?= (int)$r['distance_m'] ?> m</td>
                        <td class="text-center"><?= View::e($r['boat_class']) ?></td>
                        <td class="text-end font-monospace"><?= View::e(CanoeingRaceResultModel::formatTime((int)$r['finish_time_ms'])) ?></td>
                        <td class="text-end small text-warning">
                            <?= (float)$r['penalties_seconds'] > 0 ? '+' . number_format((float)$r['penalties_seconds'], 2) . 's' : '—' ?>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$r['rank'] === 1): ?>
                                <i class="bi bi-trophy-fill text-warning"></i> 1
                            <?php elseif ($r['rank'] !== null): ?>
                                <?= (int)$r['rank'] ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
