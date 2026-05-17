<?php
use App\Helpers\View;
use App\Sports\Mma\Models\MmaResultModel;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">
            <i class="bi bi-person-bounding-box text-primary me-2"></i>
            Kartoteka MMA — <?= View::e(($member['last_name'] ?? '') . ' ' . ($member['first_name'] ?? '')) ?>
        </h4>
        <div class="small text-muted">#<?= View::e($member['member_number'] ?? '—') ?></div>
    </div>
    <a href="<?= url('mma/results') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Walki
    </a>
</div>

<?php $r = $record ?? []; ?>
<form method="POST" action="<?= url('mma/record/' . (int)$member['id'] . '/update') ?>" class="card shadow-sm mb-4">
    <?= csrf_field() ?>
    <div class="card-header"><i class="bi bi-pencil-square me-1"></i> Edycja kartoteki MMA</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-2"><label class="form-label">Wygrane</label>
                <input type="number" min="0" class="form-control" name="wins" value="<?= (int)($r['wins'] ?? 0) ?>">
            </div>
            <div class="col-md-2"><label class="form-label">Porazki</label>
                <input type="number" min="0" class="form-control" name="losses" value="<?= (int)($r['losses'] ?? 0) ?>">
            </div>
            <div class="col-md-2"><label class="form-label">Remisy</label>
                <input type="number" min="0" class="form-control" name="draws" value="<?= (int)($r['draws'] ?? 0) ?>">
            </div>
            <div class="col-md-2"><label class="form-label">KO</label>
                <input type="number" min="0" class="form-control" name="ko_wins" value="<?= (int)($r['ko_wins'] ?? 0) ?>">
            </div>
            <div class="col-md-2"><label class="form-label">Submission</label>
                <input type="number" min="0" class="form-control" name="sub_wins" value="<?= (int)($r['sub_wins'] ?? 0) ?>">
            </div>
            <div class="col-md-2"><label class="form-label">Decyzja</label>
                <input type="number" min="0" class="form-control" name="dec_wins" value="<?= (int)($r['dec_wins'] ?? 0) ?>">
            </div>

            <div class="col-md-4"><label class="form-label">Aktualna kat. wagowa</label>
                <input type="text" maxlength="50" class="form-control" name="current_weight_class" value="<?= View::e($r['current_weight_class'] ?? '') ?>">
            </div>
            <div class="col-md-2"><label class="form-label">Stance</label>
                <select name="stance" class="form-select">
                    <?php foreach ($stances as $code => $label): ?>
                        <option value="<?= View::e($code) ?>" <?= ($r['stance'] ?? 'orthodox') === $code ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">Reach (cm)</label>
                <input type="number" min="0" class="form-control" name="reach_cm" value="<?= View::e($r['reach_cm'] ?? '') ?>">
            </div>

            <div class="col-12">
                <hr>
                <h6 class="text-muted">Discipline mix (% — suma normalizowana do 100)</h6>
            </div>
            <div class="col-md-4"><label class="form-label">Striking %</label>
                <input type="number" min="0" max="100" class="form-control" name="pct_striking" value="<?= (int)($r['pct_striking'] ?? 33) ?>">
            </div>
            <div class="col-md-4"><label class="form-label">Wrestling %</label>
                <input type="number" min="0" max="100" class="form-control" name="pct_wrestling" value="<?= (int)($r['pct_wrestling'] ?? 33) ?>">
            </div>
            <div class="col-md-4"><label class="form-label">Grappling/BJJ %</label>
                <input type="number" min="0" max="100" class="form-control" name="pct_grappling" value="<?= (int)($r['pct_grappling'] ?? 34) ?>">
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <button class="btn btn-success"><i class="bi bi-save me-1"></i> Zapisz kartoteke</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-clock-history me-1"></i> Walki MMA zawodnika</div>
    <div class="card-body p-0">
        <?php if (empty($results)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak walk.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Przeciwnik</th><th>Wynik</th><th>Sposob</th><th>Runda</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $f):
                        $ri = MmaResultModel::$RESULTS[$f['result']] ?? ['label' => '—', 'class' => 'secondary'];
                    ?>
                        <tr>
                            <td class="small"><?= View::e($f['event_date'] ?? '') ?></td>
                            <td><?= View::e($f['opponent_name'] ?? '—') ?></td>
                            <td><?php if (!empty($f['result'])): ?><span class="badge bg-<?= $ri['class'] ?>"><?= View::e($ri['label']) ?></span><?php endif; ?></td>
                            <td class="small"><?= View::e(MmaResultModel::$METHODS[$f['method'] ?? ''] ?? '—') ?></td>
                            <td class="small font-monospace"><?= !empty($f['round']) ? 'R' . (int)$f['round'] : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
