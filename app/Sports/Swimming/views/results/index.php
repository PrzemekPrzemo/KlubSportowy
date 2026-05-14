<?php use App\Helpers\View; use App\Sports\Swimming\Models\SwimmingResultModel; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-water me-2"></i>Wyniki pływania</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Data</th>
                    <th>Styl</th>
                    <th>Dystans</th>
                    <th>Basen</th>
                    <th>Czas</th>
                    <th>Kat. wiek.</th>
                    <th>PB</th>
                    <th>Miejsce</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $r):
                    $medal = match((int)$r['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                ?>
                    <tr>
                        <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                        <td><?= View::e($r['score_date']) ?></td>
                        <td><?= View::e($strokes[$r['stroke']] ?? $r['stroke']) ?></td>
                        <td><?= (int)$r['distance_m'] ?> m</td>
                        <td><?= View::e($poolTypes[$r['pool_type']] ?? $r['pool_type']) ?></td>
                        <td class="font-monospace fw-semibold"><?= SwimmingResultModel::formatTime((int)$r['time_ms']) ?></td>
                        <td><?= View::e($r['age_category'] ?? '—') ?></td>
                        <td>
                            <?php if ($r['personal_best']): ?>
                                <span class="badge bg-warning text-dark">PB</span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $medal ?> <?= $r['placement'] ? View::e($r['placement']).'.' : '—' ?></td>
                        <td>
                            <form method="POST" action="<?= url('swimming/results/'.(int)$r['id'].'/delete') ?>"
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
            <form method="POST" action="<?= url('swimming/results/store') ?>" id="swimmingForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-water me-1"></i> Dodaj wynik pływania</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Zawodnik *</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>">
                                        <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nazwa zawodów / trening</label>
                            <input type="text" name="competition_name" class="form-control"
                                   placeholder="np. Mistrzostwa Polski Juniorów">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Data *</label>
                            <input type="date" name="score_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Styl *</label>
                            <select name="stroke" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($strokes as $key => $label): ?>
                                    <option value="<?= View::e($key) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dystans *</label>
                            <select name="distance_m" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <optgroup label="Basen krótki 25m">
                                    <?php foreach ([25,50,100,200,400,800,1500] as $d): ?>
                                        <option value="<?= $d ?>"><?= $d ?> m</option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Basen olimpijski 50m">
                                    <?php foreach ([50,100,200,400,800,1500] as $d): ?>
                                        <option value="<?= $d ?>"><?= $d ?> m</option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Woda otwarta">
                                    <?php foreach ([1500,3000,5000,10000] as $d): ?>
                                        <option value="<?= $d ?>"><?= $d ?> m</option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Basen *</label>
                            <select name="pool_type" class="form-select" required>
                                <?php foreach ($poolTypes as $key => $label): ?>
                                    <option value="<?= View::e($key) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Czas * <small class="text-muted">(min : sek . cs)</small></label>
                            <div class="input-group">
                                <input type="number" id="time_min" name="time_min" class="form-control"
                                       min="0" max="99" placeholder="min" required>
                                <span class="input-group-text">:</span>
                                <input type="number" id="time_sec" name="time_sec" class="form-control"
                                       min="0" max="59" placeholder="sek" required>
                                <span class="input-group-text">.</span>
                                <input type="number" id="time_cs" name="time_cs" class="form-control"
                                       min="0" max="99" placeholder="cs" required>
                            </div>
                            <div class="form-text">np. 1 min 54 sek 32 cs = 1:54.32</div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Kategoria wiekowa</label>
                            <input type="text" name="age_category" class="form-control" placeholder="np. U18, Junior, Senior">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Miejsce</label>
                            <input type="number" name="placement" class="form-control" min="1" placeholder="np. 1">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="personal_best"
                                       id="personal_best" value="1">
                                <label class="form-check-label" for="personal_best">
                                    Rekord osobisty (PB)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-water me-1"></i> Zapisz wynik
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
