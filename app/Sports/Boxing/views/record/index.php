<?php
use App\Helpers\View;
use App\Sports\Boxing\Models\BoxingRecordModel;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">
            <i class="bi bi-card-text text-primary me-2"></i>
            Kartoteka bokserska — <?= View::e(($member['last_name'] ?? '') . ' ' . ($member['first_name'] ?? '')) ?>
        </h4>
        <div class="small text-muted">#<?= View::e($member['member_number'] ?? '—') ?></div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('boxing/record/' . (int)$member['id'] . '/weight') ?>" class="btn btn-outline-info btn-sm">
            <i class="bi bi-graph-up"></i> Historia wazenia
        </a>
        <a href="<?= url('boxing/results') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Walki
        </a>
    </div>
</div>

<?php $r = $record ?? []; ?>
<form method="POST" action="<?= url('boxing/record/' . (int)$member['id'] . '/update') ?>" class="card shadow-sm mb-4">
    <?= csrf_field() ?>
    <div class="card-header"><i class="bi bi-pencil-square me-1"></i> Edycja kartoteki</div>
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
            <div class="col-md-2"><label class="form-label">TKO</label>
                <input type="number" min="0" class="form-control" name="tko_wins" value="<?= (int)($r['tko_wins'] ?? 0) ?>">
            </div>
            <div class="col-md-2"><label class="form-label">Reach (cm)</label>
                <input type="number" min="0" class="form-control" name="reach_cm" value="<?= View::e($r['reach_cm'] ?? '') ?>">
            </div>

            <div class="col-md-3"><label class="form-label">Poziom licencji</label>
                <select name="license_level" class="form-select">
                    <?php foreach ($licenseLevels as $code => $info): ?>
                        <option value="<?= View::e($code) ?>" <?= ($r['license_level'] ?? 'junior') === $code ? 'selected' : '' ?>>
                            <?= View::e($info['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Numer licencji</label>
                <input type="text" class="form-control" name="license_number" maxlength="50" value="<?= View::e($r['license_number'] ?? '') ?>">
            </div>
            <div class="col-md-2"><label class="form-label">Licencja wazna do</label>
                <input type="date" class="form-control" name="license_expires" value="<?= View::e($r['license_expires'] ?? '') ?>">
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
            <div class="col-md-2"><label class="form-label">Akt. waga (kg)</label>
                <input type="number" min="0" step="0.01" class="form-control" name="current_weight_kg" value="<?= View::e($r['current_weight_kg'] ?? '') ?>">
            </div>

            <div class="col-md-4"><label class="form-label">Aktualna kat. wagowa</label>
                <select name="current_weight_class" class="form-select">
                    <option value="">— wybierz —</option>
                    <?php foreach ($weightClasses as $code => $label): ?>
                        <option value="<?= View::e($code) ?>" <?= ($r['current_weight_class'] ?? '') === $code ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="card-footer text-end">
        <button class="btn btn-success"><i class="bi bi-save me-1"></i> Zapisz kartoteke</button>
    </div>
</form>

<!-- Snapshot ostatnich walk -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-clock-history me-1"></i> Walki zawodnika</div>
    <div class="card-body p-0">
        <?php if (empty($results)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak walk.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Przeciwnik</th><th>Wynik</th><th>Sposob</th><th>Kat. waga</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $f): ?>
                        <tr>
                            <td class="small text-muted"><?= View::e($f['competition_date']) ?></td>
                            <td><?= View::e($f['opponent_name'] ?? '—') ?></td>
                            <td><?= View::e($f['result'] ?? '—') ?></td>
                            <td class="small"><?= View::e($f['method'] ?? '—') ?></td>
                            <td class="small text-muted"><?= View::e($f['weight_class'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
