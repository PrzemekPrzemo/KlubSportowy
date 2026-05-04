<?php
use App\Helpers\View;
use App\Sports\Biathlon\Models\BiathlonResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bullseye text-primary me-2"></i>Wyniki — Biathlon</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Zawodnik</th><th>Format</th><th>Dystans</th><th>Czas biegu</th><th>Strzelanie</th><th>Kary</th><th>Total</th><th>#</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: foreach ($results as $r):
                $acc = BiathlonResultModel::accuracy($r['shootings_total'], $r['misses_total']);
            ?>
                <tr class="<?= $r['dnf'] || $r['dns'] ? 'table-warning' : '' ?>">
                    <td class="small"><?= View::e($r['event_date']) ?></td>
                    <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= View::e($formats[$r['format']] ?? $r['format']) ?></span></td>
                    <td class="font-monospace"><?= number_format((float)$r['distance_km'], 1) ?> km</td>
                    <td class="font-monospace"><?= BiathlonResultModel::formatTime($r['run_time_s']) ?></td>
                    <td class="small">
                        <?php if ($r['shootings_total']): ?>
                            <?= (int)$r['shootings_total'] - (int)($r['misses_total'] ?? 0) ?>/<?= (int)$r['shootings_total'] ?>
                            <?php if ($acc !== null): ?><small class="text-muted d-block"><?= $acc ?>%</small><?php endif; ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if ($r['penalty_laps']): ?><?= (int)$r['penalty_laps'] ?> pętle<br><?php endif; ?>
                        <?php if ($r['penalty_time_s']): ?><small class="text-muted">+<?= BiathlonResultModel::formatTime($r['penalty_time_s']) ?></small><?php endif; ?>
                    </td>
                    <td class="font-monospace fw-bold">
                        <?php if ($r['dnf']): ?>DNF<?php elseif ($r['dns']): ?>DNS<?php else: ?><?= BiathlonResultModel::formatTime($r['total_time_s']) ?><?php endif; ?>
                    </td>
                    <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                    <td>
                        <form method="POST" action="<?= url('biathlon/results/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="resModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('biathlon/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj wynik biathlonu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Format</label>
                            <select name="format" class="form-select">
                                <?php foreach ($formats as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-8"><label class="form-label">Zawody</label>
                            <input type="text" name="event_name" class="form-control" required>
                        </div>
                        <div class="col-4"><label class="form-label">Data</label>
                            <input type="date" name="event_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6"><label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" placeholder="np. Ruhpolding">
                        </div>
                        <div class="col-3"><label class="form-label">Dystans (km)</label>
                            <input type="number" step="0.01" name="distance_km" class="form-control" required>
                        </div>
                        <div class="col-3"><label class="form-label">Czas biegu (sek)</label>
                            <input type="number" name="run_time_s" class="form-control" min="0">
                        </div>
                        <div class="col-3"><label class="form-label">Strzały (łącznie)</label>
                            <input type="number" name="shootings_total" class="form-control" min="0" max="100">
                        </div>
                        <div class="col-3"><label class="form-label">Pudła</label>
                            <input type="number" name="misses_total" class="form-control" min="0" max="100">
                        </div>
                        <div class="col-3"><label class="form-label">Pętle karne</label>
                            <input type="number" name="penalty_laps" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-3"><label class="form-label">Czas kar (s)</label>
                            <input type="number" name="penalty_time_s" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-3"><label class="form-label">Miejsce</label>
                            <input type="number" name="place" class="form-control" min="1">
                        </div>
                        <div class="col-3"><label class="form-label">FIS pkt</label>
                            <input type="number" step="0.01" name="fis_points" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">Kategoria</label>
                            <input type="text" name="category" class="form-control">
                        </div>
                        <div class="col-8">
                            <div class="form-check form-check-inline mt-4"><input type="checkbox" name="dnf" id="bdnf" class="form-check-input"><label for="bdnf" class="form-check-label">DNF</label></div>
                            <div class="form-check form-check-inline mt-4"><input type="checkbox" name="dns" id="bdns" class="form-check-input"><label for="bdns" class="form-check-label">DNS</label></div>
                        </div>
                        <div class="col-12"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
