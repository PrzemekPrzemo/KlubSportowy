<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-person-bounding-box text-primary me-2"></i>Zawodnicy MMA</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#fModal">
        <i class="bi bi-plus-circle"></i> Dodaj/edytuj profil
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Zawodnik</th><th>Nick</th><th>Stance</th><th>Waga</th><th>Styl</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($fighters)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Brak zawodników.</td></tr>
            <?php else: foreach ($fighters as $f): ?>
                <tr>
                    <td><strong><?= View::e($f['last_name'] . ' ' . $f['first_name']) ?></strong> <small class="text-muted">#<?= View::e($f['member_number']) ?></small></td>
                    <td><?= View::e($f['nickname'] ?? '—') ?></td>
                    <td><small><?= View::e($stances[$f['stance']] ?? $f['stance']) ?></small></td>
                    <td><span class="badge bg-secondary"><?= View::e($f['weight_class'] ?? '—') ?></span></td>
                    <td><span class="badge bg-dark"><?= View::e($styles[$f['primary_style']] ?? $f['primary_style']) ?></span></td>
                    <td>
                        <form method="POST" action="<?= url('mma/fighters/' . (int)$f['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="fModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('mma/fighters/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Profil zawodnika MMA</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="alert alert-info small">Jeśli zawodnik istnieje — wpis zostanie zaktualizowany.</div>
                    <div class="mb-3"><label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $mm): ?>
                                <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Pseudonim</label><input type="text" name="nickname" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Kategoria wagowa</label><input type="text" name="weight_class" class="form-control" placeholder="np. lekka, średnia"></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Stance</label>
                            <select name="stance" class="form-select">
                                <?php foreach ($stances as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Styl główny</label>
                            <select name="primary_style" class="form-select">
                                <?php foreach ($styles as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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
