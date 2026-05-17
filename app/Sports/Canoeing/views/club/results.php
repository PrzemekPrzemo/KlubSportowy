<?php use App\Helpers\View;
use App\Sports\Canoeing\Models\CanoeingRaceResultModel; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-stopwatch-fill text-primary me-2"></i>Kajakarstwo — Wyniki wyscigow</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Turniej</th>
                    <th>Data</th>
                    <th>Zawodnik</th>
                    <th class="text-center">Dystans</th>
                    <th class="text-center">Lodz</th>
                    <th class="text-end">Czas</th>
                    <th class="text-end">Kary</th>
                    <th class="text-center">Pozycja</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak wynikow.</td></tr>
            <?php else: foreach ($results as $r): ?>
                <tr>
                    <td><?= View::e($r['tournament_name']) ?></td>
                    <td class="small text-muted"><?= View::e($r['tournament_date']) ?></td>
                    <td><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></td>
                    <td class="text-center"><?= (int)$r['distance_m'] ?> m</td>
                    <td class="text-center"><span class="badge bg-info text-dark"><?= View::e($r['boat_class']) ?></span></td>
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
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('club/canoeing/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-stopwatch-fill me-1"></i>Dodaj wynik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Turniej</label>
                            <select name="tournament_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($tournaments as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>">
                                        <?= View::e($t['name'] . ' (' . $t['date_start'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($tournaments)): ?>
                                <small class="text-danger">Brak turniejow.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Dystans</label>
                            <select name="distance_m" class="form-select" required>
                                <?php foreach ($distances as $m => $label): ?>
                                    <option value="<?= (int)$m ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Klasa lodzi</label>
                            <select name="boat_class" class="form-select" required>
                                <?php foreach ($boatClasses as $k => $label): ?>
                                    <option value="<?= View::e($k) ?>"><?= View::e($k) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Czas (M:SS.mmm)</label>
                            <input type="text" name="finish_time" class="form-control font-monospace"
                                   required placeholder="1:45.230" pattern="\d+:?\d*:?\d+(\.\d{1,3})?">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Kary (s)</label>
                            <input type="number" name="penalties_seconds" class="form-control"
                                   step="0.01" min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button class="btn btn-success">Zapisz + przelicz ranking</button>
                </div>
            </form>
        </div>
    </div>
</div>
