<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people text-primary me-2"></i>Pary brydżowe</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pModal">
        <i class="bi bi-plus-circle"></i> Dodaj parę
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Nazwa</th><th>Gracz 1</th><th>Gracz 2</th><th>Kategoria</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($partnerships)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Brak par.</td></tr>
            <?php else: foreach ($partnerships as $p): ?>
                <tr class="<?= !$p['active'] ? 'text-muted' : '' ?>">
                    <td><strong><?= View::e($p['name'] ?? ($p['p1_last'] . ' / ' . $p['p2_last'])) ?></strong></td>
                    <td><?= View::e($p['p1_last'] . ' ' . $p['p1_first']) ?></td>
                    <td><?= View::e($p['p2_last'] . ' ' . $p['p2_first']) ?></td>
                    <td><span class="badge bg-secondary"><?= View::e($categories[$p['category']] ?? $p['category']) ?></span></td>
                    <td>
                        <?php if ($p['active']): ?>
                            <span class="badge bg-success">Aktywna</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Nieaktywna</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('bridge/partnerships/' . (int)$p['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="pModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('bridge/partnerships/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj parę</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Gracz 1</label>
                            <select name="player1_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Gracz 2</label>
                            <select name="player2_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-8"><label class="form-label">Nazwa pary (opcjonalna)</label>
                            <input type="text" name="name" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">Kategoria</label>
                            <select name="category" class="form-select">
                                <?php foreach ($categories as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-check"><input type="checkbox" name="active" id="bact" class="form-check-input" checked><label for="bact" class="form-check-label">Aktywna</label></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
