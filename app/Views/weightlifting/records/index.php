<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Rekordy — Podnoszenie ciężarów</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#recordModal">
        <i class="bi bi-plus-circle"></i> Dodaj rekord
    </button>
</div>

<!-- Club records summary -->
<?php if (!empty($clubRecords)): ?>
    <div class="card shadow-sm mb-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <i class="bi bi-star-fill me-1"></i> Aktualne rekordy klubu
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Kategoria wagowa</th><th>Bój</th><th>Wartość</th><th>Zawodnik</th><th>Data</th></tr>
                </thead>
                <tbody>
                <?php foreach ($clubRecords as $r):
                    $li = $lifts[$r['lift']] ?? ['label' => $r['lift'], 'class' => 'secondary'];
                ?>
                    <tr>
                        <td><span class="badge bg-dark"><?= View::e($r['weight_class']) ?> kg</span></td>
                        <td><span class="badge bg-<?= $li['class'] ?>"><?= View::e($li['label']) ?></span></td>
                        <td class="font-monospace fw-bold text-success fs-5"><?= number_format((float)$r['value_kg'], 1) ?> kg</td>
                        <td><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></td>
                        <td class="small text-muted"><?= View::e($r['set_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="mb-3 d-flex gap-2">
    <a href="<?= url('weightlifting/records') ?>" class="btn btn-sm btn-<?= !$typeFilter ? 'primary' : 'outline-secondary' ?>">Wszystkie</a>
    <?php foreach ($recordTypes as $k => $t): ?>
        <a href="?type=<?= urlencode($k) ?>" class="btn btn-sm btn-<?= $typeFilter === $k ? 'primary' : ('outline-' . $t['class']) ?>"><?= View::e($t['label']) ?></a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header">Wszystkie rekordy</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Zawodnik</th><th>Typ</th><th>Bój</th><th>Kat.</th><th>Wartość</th><th>Zawody</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($records)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak rekordów.</td></tr>
            <?php else: foreach ($records as $r):
                $ti = $recordTypes[$r['record_type']] ?? ['label' => $r['record_type'], 'class' => 'secondary'];
                $li = $lifts[$r['lift']] ?? ['label' => $r['lift'], 'class' => 'secondary'];
            ?>
                <tr>
                    <td class="small text-muted"><?= View::e($r['set_at']) ?></td>
                    <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                    <td><span class="badge bg-<?= $ti['class'] ?>"><?= View::e($ti['label']) ?></span></td>
                    <td><span class="badge bg-<?= $li['class'] ?>"><?= View::e($li['label']) ?></span></td>
                    <td><?= View::e($r['weight_class']) ?> kg</td>
                    <td class="font-monospace fw-bold"><?= number_format((float)$r['value_kg'], 1) ?> kg</td>
                    <td class="small"><?= View::e($r['event_name'] ?? '—') ?></td>
                    <td>
                        <form method="POST" action="<?= url('weightlifting/records/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="recordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('weightlifting/records/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj rekord</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $mm): ?>
                                <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label">Typ</label>
                            <select name="record_type" class="form-select">
                                <?php foreach ($recordTypes as $k => $t): ?>
                                    <option value="<?= $k ?>"><?= View::e($t['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Bój</label>
                            <select name="lift" class="form-select">
                                <?php foreach ($lifts as $k => $l): ?>
                                    <option value="<?= $k ?>"><?= View::e($l['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Kategoria (kg)</label>
                            <select name="weight_class" class="form-select">
                                <optgroup label="Mężczyźni">
                                    <?php foreach ($weightClassesM as $wc): ?>
                                        <option value="<?= View::e($wc) ?>"><?= View::e($wc) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Kobiety">
                                    <?php foreach ($weightClassesW as $wc): ?>
                                        <option value="<?= View::e($wc) ?>"><?= View::e($wc) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Wartość (kg)</label>
                            <input type="number" step="0.5" name="value_kg" class="form-control" min="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Data</label>
                            <input type="date" name="set_at" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zawody / miejsce</label>
                        <input type="text" name="event_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uwagi</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
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
