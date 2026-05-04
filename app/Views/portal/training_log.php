<?php
use App\Helpers\View;
use App\Models\AthleteTrainingLogModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-journal-bookmark text-primary me-2"></i>Dziennik treningowy</h3>
        <p class="text-muted mb-0">Logowanie sesji treningowych — dla cyclingu, pływania, weightliftingu, triathlonu</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Dashboard
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#logModal">
            <i class="bi bi-plus-circle"></i> Zaloguj trening
        </button>
    </div>
</div>

<!-- Week summary -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center"><div class="card-body p-2">
            <div class="text-muted small">Sesje (tydz.)</div>
            <h3 class="mb-0 text-primary"><?= (int)($weekTotal['sessions'] ?? 0) ?></h3>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center"><div class="card-body p-2">
            <div class="text-muted small">Minuty</div>
            <h3 class="mb-0"><?= (int)($weekTotal['minutes'] ?? 0) ?></h3>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center"><div class="card-body p-2">
            <div class="text-muted small">Dystans (km)</div>
            <h3 class="mb-0"><?= number_format((float)($weekTotal['km'] ?? 0), 1) ?></h3>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center"><div class="card-body p-2">
            <div class="text-muted small">Tonaż (kg)</div>
            <h3 class="mb-0"><?= (int)($weekTotal['volume_kg'] ?? 0) ?></h3>
        </div></div>
    </div>
</div>

<!-- Weekly grid -->
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><i class="bi bi-calendar-week me-1"></i> Tydzień: <?= View::e($weekStart) ?> — <?= View::e(date('Y-m-d', strtotime($weekStart . ' +6 days'))) ?></div>
        <div class="btn-group btn-group-sm">
            <a href="?week=<?= date('Y-m-d', strtotime($weekStart . ' -7 days')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-chevron-left"></i>
            </a>
            <a href="<?= url('portal/training-log') ?>" class="btn btn-outline-primary">Dziś</a>
            <a href="?week=<?= date('Y-m-d', strtotime($weekStart . ' +7 days')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>Dzień</th><th>Typ</th><th>Sport</th><th>Czas</th><th>Dystans</th><th>Tonaż</th><th>HR śr.</th><th>Moc (W)</th><th>RPE</th><th>Notatki</th><th></th></tr>
            </thead>
            <tbody>
            <?php
            $days = ['Pn','Wt','Śr','Cz','Pt','Sb','Nd'];
            for ($i = 0; $i < 7; $i++):
                $d = date('Y-m-d', strtotime($weekStart . ' +' . $i . ' days'));
                $dayLogs = array_filter($weekLogs, fn($l) => $l['log_date'] === $d);
                $isToday = $d === date('Y-m-d');
                if (empty($dayLogs)):
            ?>
                <tr class="<?= $isToday ? 'table-warning' : '' ?>">
                    <td><strong><?= $days[$i] ?></strong> <small class="text-muted"><?= $d ?></small></td>
                    <td colspan="10" class="text-muted small"><em>brak sesji</em></td>
                </tr>
            <?php else: foreach ($dayLogs as $idx => $l):
                $ti = AthleteTrainingLogModel::$SESSION_TYPES[$l['session_type']] ?? ['label' => $l['session_type'], 'class' => 'secondary'];
            ?>
                <tr class="<?= $isToday ? 'table-warning' : '' ?>">
                    <td>
                        <?php if ($idx === array_key_first($dayLogs)): ?>
                            <strong><?= $days[$i] ?></strong> <small class="text-muted"><?= $d ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-<?= $ti['class'] ?>"><?= View::e($ti['label']) ?></span></td>
                    <td class="small text-muted"><?= View::e($l['sport_key'] ?? '—') ?></td>
                    <td class="small"><?= $l['duration_min'] ? (int)$l['duration_min'] . ' min' : '—' ?></td>
                    <td class="small"><?= $l['distance_km'] ? number_format((float)$l['distance_km'], 1) . ' km' : '—' ?></td>
                    <td class="small"><?= $l['volume_kg'] ? (int)$l['volume_kg'] . ' kg' : '—' ?></td>
                    <td class="small"><?= $l['avg_hr'] ? (int)$l['avg_hr'] : '—' ?></td>
                    <td class="small"><?= $l['avg_power_w'] ? (int)$l['avg_power_w'] : '—' ?></td>
                    <td>
                        <?php if ($l['intensity']): ?>
                            <span class="badge bg-<?= $l['intensity'] >= 8 ? 'danger' : ($l['intensity'] >= 6 ? 'warning' : 'success') ?>">
                                <?= (int)$l['intensity'] ?>/10
                            </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="small"><?= View::e(mb_substr($l['notes'] ?? '', 0, 40)) ?></td>
                    <td>
                        <form method="POST" action="<?= url('portal/training-log/' . (int)$l['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="logModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('portal/training-log/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nowa sesja treningowa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label">Data</label>
                            <input type="date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Typ sesji</label>
                            <select name="session_type" class="form-select">
                                <?php foreach (AthleteTrainingLogModel::$SESSION_TYPES as $k => $t): ?>
                                    <option value="<?= $k ?>"><?= View::e($t['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Sport (opcjonalny)</label>
                            <input type="text" name="sport_key" class="form-control" placeholder="np. swimming, cycling">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Czas (min)</label>
                            <input type="number" name="duration_min" class="form-control" min="1" max="1440">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Dystans (km)</label>
                            <input type="number" step="0.1" name="distance_km" class="form-control" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Tonaż (kg — weightlifting)</label>
                            <input type="number" name="volume_kg" class="form-control" min="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label">HR śr. (bpm)</label>
                            <input type="number" name="avg_hr" class="form-control" min="30" max="220">
                        </div>
                        <div class="col-3">
                            <label class="form-label">HR max (bpm)</label>
                            <input type="number" name="max_hr" class="form-control" min="30" max="220">
                        </div>
                        <div class="col-3">
                            <label class="form-label">Moc śr. (W — cycling)</label>
                            <input type="number" name="avg_power_w" class="form-control" min="0" max="600">
                        </div>
                        <div class="col-3">
                            <label class="form-label">RPE (1-10)</label>
                            <input type="number" name="intensity" class="form-control" min="1" max="10">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notatki (opis treningu, samopoczucie)</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="np. 5x200m na 2:30, czułem się ciężko..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zapisz sesję</button>
                </div>
            </form>
        </div>
    </div>
</div>
