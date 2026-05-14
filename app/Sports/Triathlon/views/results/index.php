<?php
use App\Helpers\View;
use App\Sports\Triathlon\Models\TriathlonResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki — Triathlon</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
    <a href="<?= url('triathlon/results') ?>" class="btn btn-sm <?= empty($filterDist) ? 'btn-primary' : 'btn-outline-primary' ?>">Wszystkie</a>
    <?php foreach ($distances as $d): ?>
        <a href="<?= url('triathlon/results?distance=' . urlencode($d)) ?>"
           class="btn btn-sm <?= $filterDist === $d ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= strtoupper($d) ?>
        </a>
    <?php endforeach; ?>
    <form method="GET" class="d-flex gap-1 ms-2">
        <?php if ($filterDist): ?><input type="hidden" name="distance" value="<?= View::e($filterDist) ?>"><?php endif; ?>
        <input type="number" name="year" class="form-control form-control-sm" style="width:90px" value="<?= View::e((string)($filterYear ?? '')) ?>" placeholder="Rok">
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th><th>Zawody</th><th>Data</th><th>Dystans</th>
                    <th class="text-center">Pływanie</th><th class="text-center">T1</th>
                    <th class="text-center">Rower</th><th class="text-center">T2</th>
                    <th class="text-center">Bieg</th><th class="text-center fw-bold">Total</th>
                    <th class="text-center">AG</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="12" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $r): ?>
                <tr class="<?= $r['dnf'] ? 'text-muted' : ($r['dns'] ? 'text-muted' : '') ?>">
                    <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                    <td><?= View::e($r['event_name']) ?></td>
                    <td><?= View::e($r['event_date']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= strtoupper($r['distance_type']) ?></span></td>
                    <td class="text-center small"><?= TriathlonResultModel::formatTime((int)$r['swim_time'] ?: null) ?></td>
                    <td class="text-center small text-muted"><?= TriathlonResultModel::formatTime((int)$r['t1_time'] ?: null) ?></td>
                    <td class="text-center small"><?= TriathlonResultModel::formatTime((int)$r['bike_time'] ?: null) ?></td>
                    <td class="text-center small text-muted"><?= TriathlonResultModel::formatTime((int)$r['t2_time'] ?: null) ?></td>
                    <td class="text-center small"><?= TriathlonResultModel::formatTime((int)$r['run_time'] ?: null) ?></td>
                    <td class="text-center fw-bold">
                        <?php if ($r['dnf']): ?><span class="badge bg-danger">DNF</span>
                        <?php elseif ($r['dns']): ?><span class="badge bg-secondary">DNS</span>
                        <?php else: ?><?= TriathlonResultModel::formatTime((int)$r['total_time'] ?: null) ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-center small"><?= $r['ag_placement'] ? 'AG#' . (int)$r['ag_placement'] : '—' ?></td>
                    <td>
                        <form method="POST" action="<?= url('triathlon/results/' . (int)$r['id'] . '/delete') ?>"
                              onsubmit="return confirm('Usunąć wynik?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Dodaj wynik -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('triathlon/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-stopwatch me-1"></i> Dodaj wynik triatlonu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Zawody</label><input type="text" name="event_name" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Data</label><input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Dystans</label>
                            <select name="distance_type" class="form-select">
                                <?php foreach ($distances as $d): ?><option value="<?= $d ?>"><?= strtoupper($d) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Kategoria wiekowa</label><input type="text" name="age_group" class="form-control" placeholder="np. M40-44"></div>
                        <div class="col-12"><p class="text-muted small mb-1">Czasy w sekundach (np. 1800 = 30 min)</p></div>
                        <div class="col-2"><label class="form-label">Pływanie</label><input type="number" name="swim_time" class="form-control" min="0"></div>
                        <div class="col-2"><label class="form-label">T1</label><input type="number" name="t1_time" class="form-control" min="0"></div>
                        <div class="col-2"><label class="form-label">Rower</label><input type="number" name="bike_time" class="form-control" min="0"></div>
                        <div class="col-2"><label class="form-label">T2</label><input type="number" name="t2_time" class="form-control" min="0"></div>
                        <div class="col-2"><label class="form-label">Bieg</label><input type="number" name="run_time" class="form-control" min="0"></div>
                        <div class="col-2"><label class="form-label">Total</label><input type="number" name="total_time" class="form-control" min="0" placeholder="auto"></div>
                        <div class="col-md-3"><label class="form-label">Miejsce AG</label><input type="number" name="ag_placement" class="form-control" min="1"></div>
                        <div class="col-md-3"><label class="form-label">Miejsce ogólne</label><input type="number" name="overall_placement" class="form-control" min="1"></div>
                        <div class="col-md-6"><div class="d-flex gap-3 mt-3">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="dnf" id="chk-dnf" value="1"><label class="form-check-label" for="chk-dnf">DNF</label></div>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="dns" id="chk-dns" value="1"><label class="form-check-label" for="chk-dns">DNS</label></div>
                        </div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
