<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people me-2"></i>Padel — Pary debla</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pairModal">
        <i class="bi bi-plus-circle"></i> Dodaj parę
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nazwa pary</th><th>Zawodnik A</th><th>Zawodnik B</th>
                    <th>Punkty rank.</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pairs)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Brak par.</td></tr>
            <?php else: foreach ($pairs as $p): ?>
                <tr>
                    <td><strong><?= View::e($p['pair_name'] ?? '—') ?></strong></td>
                    <td><?= View::e($p['a_last'] . ' ' . $p['a_first']) ?>
                        <small class="text-muted">#<?= View::e((string)$p['a_num']) ?></small></td>
                    <td><?= View::e($p['b_last'] . ' ' . $p['b_first']) ?>
                        <small class="text-muted">#<?= View::e((string)$p['b_num']) ?></small></td>
                    <td><span class="badge bg-primary"><?= (int)$p['ranking_points'] ?></span></td>
                    <td>
                        <?php if ((int)$p['active']): ?>
                            <span class="badge bg-success">Aktywna</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Zawieszona</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('club/sport/padel/pairs/' . (int)$p['id'] . '/toggle') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-toggle-on"></i></button>
                        </form>
                        <form method="POST" action="<?= url('club/sport/padel/pairs/' . (int)$p['id'] . '/delete') ?>"
                              class="d-inline" onsubmit="return confirm('Usunąć parę?')">
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

<div class="modal fade" id="pairModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('club/sport/padel/pairs/store') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Nowa para</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Nazwa pary</label>
                    <input name="pair_name" class="form-control" placeholder="np. Kowalski / Nowak"></div>
                <div class="mb-2"><label class="form-label">Zawodnik A *</label>
                    <select name="member_a_id" class="form-select" required>
                        <option value="">—</option>
                        <?php foreach (($members ?? []) as $m): ?>
                            <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2"><label class="form-label">Zawodnik B *</label>
                    <select name="member_b_id" class="form-select" required>
                        <option value="">—</option>
                        <?php foreach (($members ?? []) as $m): ?>
                            <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2"><label class="form-label">Początkowe punkty</label>
                    <input type="number" min="0" name="ranking_points" value="0" class="form-control"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button class="btn btn-success">Zapisz</button>
            </div>
        </form>
    </div>
</div>
