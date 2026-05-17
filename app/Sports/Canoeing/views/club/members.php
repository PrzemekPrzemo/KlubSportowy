<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-water text-primary me-2"></i>Kajakarstwo — Zawodnicy</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#memberModal">
        <i class="bi bi-plus-circle"></i> Dodaj/edytuj profil
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Klasa lodzi</th>
                    <th>Klasa wagowa</th>
                    <th class="text-end">Ranking krajowy</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Brak profili kajakarzy.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong>
                        <small class="text-muted">#<?= View::e($r['member_number'] ?? '') ?></small>
                    </td>
                    <td><span class="badge bg-info text-dark"><?= View::e($r['boat_class']) ?></span></td>
                    <td><?= View::e($r['weight_class'] ?? '—') ?></td>
                    <td class="text-end font-monospace"><?= $r['national_rank'] !== null ? (int)$r['national_rank'] : '<span class="text-muted">—</span>' ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="memberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('club/canoeing/members/save') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-water me-1"></i>Profil kajakarza</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($allMembers as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Klasa lodzi</label>
                            <select name="boat_class" class="form-select" required>
                                <?php foreach ($boatClasses as $k => $label): ?>
                                    <option value="<?= View::e($k) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Klasa wagowa</label>
                            <input type="text" name="weight_class" class="form-control" maxlength="50" placeholder="np. lightweight">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ranking krajowy</label>
                            <input type="number" name="national_rank" class="form-control" min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button class="btn btn-success">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
