<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Sprzęt (łuki) — Łucznictwo</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bowModal">
        <i class="bi bi-plus-circle"></i> Dodaj łuk
    </button>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Typ</th><th>Marka / Model</th><th>Naciąg</th><th>Długość ramion</th><th>Nr seryjny</th><th>Właściciel</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($bows)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak sprzętu.</td></tr>
        <?php else: ?>
            <?php foreach ($bows as $b): ?>
                <tr>
                    <td><span class="badge bg-primary"><?= View::e($bowTypes[$b['bow_type']] ?? $b['bow_type']) ?></span></td>
                    <td><?= View::e(trim(($b['brand'] ?? '').' '.($b['model'] ?? ''))) ?: '—' ?></td>
                    <td><?= $b['draw_weight'] ? View::e($b['draw_weight']).' lbs' : '—' ?></td>
                    <td><?= View::e($b['limb_length'] ?? '—') ?></td>
                    <td class="text-muted small"><?= View::e($b['serial_no'] ?? '—') ?></td>
                    <td>
                        <?php if ($b['owned_by'] === 'member' && $b['first_name']): ?>
                            <?= View::e($b['last_name'].' '.$b['first_name']) ?>
                        <?php else: ?>
                            <span class="text-muted">Klub</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $statusColors = ['active' => 'success', 'maintenance' => 'warning', 'retired' => 'secondary']; ?>
                        <span class="badge bg-<?= $statusColors[$b['status']] ?? 'secondary' ?>">
                            <?= ['active' => 'aktywny', 'maintenance' => 'serwis', 'retired' => 'wycofany'][$b['status']] ?? $b['status'] ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('archery/bows/'.(int)$b['id'].'/delete') ?>"
                              onsubmit="return confirm('Usunąć łuk?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Dodaj łuk -->
<div class="modal fade" id="bowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('archery/bows/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bullseye me-1"></i> Dodaj łuk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Typ łuku *</label>
                            <select name="bow_type" class="form-select" required>
                                <?php foreach ($bowTypes as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Właściciel</label>
                            <select name="owned_by" class="form-select" id="ownedBySelect">
                                <option value="club">Klub</option>
                                <option value="member">Zawodnik</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3" id="memberSelectRow" style="display:none">
                        <label class="form-label">Zawodnik (właściciel)</label>
                        <select name="member_id" class="form-select">
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Marka</label>
                            <input type="text" name="brand" class="form-control" placeholder="np. Hoyt, Win&Win">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control" placeholder="np. Formula RX9">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Naciąg (lbs)</label>
                            <input type="number" name="draw_weight" class="form-control" step="0.5" min="1" max="80">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rozciąg (cale)</label>
                            <input type="number" name="draw_length" class="form-control" step="0.5" min="15" max="32">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Długość ramion</label>
                            <select name="limb_length" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($limbLengths as $ll): ?>
                                    <option value="<?= $ll ?>"><?= $ll ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nr seryjny</label>
                        <input type="text" name="serial_no" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('ownedBySelect').addEventListener('change', function() {
    document.getElementById('memberSelectRow').style.display = this.value === 'member' ? '' : 'none';
});
</script>
