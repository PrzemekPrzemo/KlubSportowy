<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-water text-primary me-2"></i>Łodzie kajakowe</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bModal">
        <i class="bi bi-plus-circle"></i> Dodaj łódź
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Nazwa</th><th>Typ</th><th>Rocznik</th><th>Materiał</th><th>Stan</th><th>Lokalizacja</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($boats)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak łodzi.</td></tr>
            <?php else: foreach ($boats as $b):
                $si = $states[$b['state']] ?? ['label' => $b['state'], 'class' => 'secondary'];
            ?>
                <tr>
                    <td><strong><?= View::e($b['name']) ?></strong></td>
                    <td><span class="badge bg-primary"><?= View::e($b['boat_type']) ?></span></td>
                    <td class="small"><?= $b['year_built'] ? (int)$b['year_built'] : '—' ?></td>
                    <td class="small text-muted"><?= View::e($b['hull_material'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= $si['class'] ?>"><?= View::e($si['label']) ?></span></td>
                    <td class="small"><?= View::e($b['location'] ?? '—') ?></td>
                    <td>
                        <form method="POST" action="<?= url('kayaking/boats/' . (int)$b['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="bModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('kayaking/boats/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nowa łódź</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-8"><label class="form-label">Nazwa</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-4"><label class="form-label">Typ</label>
                            <select name="boat_type" class="form-select">
                                <?php foreach ($types as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Materiał</label><input type="text" name="hull_material" class="form-control"></div>
                        <div class="col-3"><label class="form-label">Rok budowy</label><input type="number" name="year_built" class="form-control" min="1950" max="2099"></div>
                        <div class="col-3"><label class="form-label">Data zakupu</label><input type="date" name="purchase_date" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Stan</label>
                            <select name="state" class="form-select">
                                <?php foreach ($states as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Lokalizacja</label><input type="text" name="location" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
