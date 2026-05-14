<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-heart-pulse text-danger me-2"></i>Badania lekarskie — Boks</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#medModal">
        <i class="bi bi-plus-circle"></i> Dodaj badanie
    </button>
</div>

<div class="alert alert-warning mb-3 small">
    <i class="bi bi-exclamation-triangle me-1"></i>
    <strong>Uwaga:</strong> zgodnie z przepisami PZBoks, zawodnik bez aktualnego badania lekarskiego nie może brać udziału w walkach ani sparingach.
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th><th>Data badania</th><th>Ważne do</th>
                    <th>Rodzaj dopuszczenia</th><th>Lekarz</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($medicals)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak badań.</td></tr>
            <?php else: foreach ($medicals as $m):
                $days = (int)($m['days_remaining'] ?? 0);
                $clearInfo = $clearanceTypes[$m['clearance_type']] ?? ['label' => $m['clearance_type'], 'class' => 'secondary'];
            ?>
                <tr>
                    <td>
                        <strong><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></strong>
                        <small class="text-muted">#<?= View::e($m['member_number']) ?></small>
                    </td>
                    <td class="small"><?= View::e($m['exam_date']) ?></td>
                    <td class="small"><?= View::e($m['valid_until']) ?></td>
                    <td><span class="badge bg-<?= $clearInfo['class'] ?>"><?= View::e($clearInfo['label']) ?></span></td>
                    <td class="small text-muted"><?= View::e($m['doctor_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($days < 0): ?>
                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>WYGASŁO (<?= abs($days) ?> dni temu)</span>
                        <?php elseif ($days <= 30): ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Kończy się za <?= $days ?> dni</span>
                        <?php else: ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aktywne (<?= $days ?> dni)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('boxing/medicals/' . (int)$m['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<!-- Modal -->
<div class="modal fade" id="medModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('boxing/medicals/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj badanie lekarskie</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
                        <div class="col-6">
                            <label class="form-label">Data badania</label>
                            <input type="date" name="exam_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Ważne do</label>
                            <input type="date" name="valid_until" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rodzaj dopuszczenia</label>
                        <select name="clearance_type" class="form-select">
                            <?php foreach ($clearanceTypes as $k => $v): ?>
                                <option value="<?= $k ?>"><?= View::e($v['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lekarz</label>
                        <input type="text" name="doctor_name" class="form-control">
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
