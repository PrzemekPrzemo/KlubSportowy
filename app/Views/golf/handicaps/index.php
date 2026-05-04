<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Handicap WHS — Golf</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#hModal">
        <i class="bi bi-plus-circle"></i> Aktualizuj handicap
    </button>
</div>

<div class="alert alert-info small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    <strong>WHS (World Handicap System)</strong> — globalny standard handicapu golfowego. W Polsce obsługiwany przez PZGA.
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Zawodnik</th><th>WHS Index</th><th>Data</th><th>Źródło</th><th>Uwagi</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($handicaps)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Brak handicapów.</td></tr>
            <?php else: foreach ($handicaps as $h): ?>
                <tr>
                    <td><strong><?= View::e($h['last_name'] . ' ' . $h['first_name']) ?></strong> <small class="text-muted">#<?= View::e($h['member_number']) ?></small></td>
                    <td class="font-monospace fw-bold fs-5 <?= (float)$h['whs_index'] <= 10 ? 'text-success' : ((float)$h['whs_index'] <= 20 ? 'text-primary' : 'text-muted') ?>">
                        <?= number_format((float)$h['whs_index'], 1) ?>
                    </td>
                    <td class="small"><?= View::e($h['updated_at']) ?></td>
                    <td><span class="badge bg-secondary"><?= View::e($sources[$h['source']] ?? $h['source']) ?></span></td>
                    <td class="small text-muted"><?= View::e($h['notes'] ?? '—') ?></td>
                    <td>
                        <form method="POST" action="<?= url('golf/handicaps/' . (int)$h['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="hModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('golf/handicaps/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Aktualizuj handicap WHS</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $mm): ?>
                                <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-4"><label class="form-label">WHS Index</label>
                            <input type="number" step="0.1" name="whs_index" class="form-control" required placeholder="np. 15.4">
                        </div>
                        <div class="col-4"><label class="form-label">Data</label>
                            <input type="date" name="updated_at" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-4"><label class="form-label">Źródło</label>
                            <select name="source" class="form-select">
                                <?php foreach ($sources as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mt-3"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
