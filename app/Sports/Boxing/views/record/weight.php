<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">
            <i class="bi bi-graph-up text-info me-2"></i>
            Historia wazenia — <?= View::e(($member['last_name'] ?? '') . ' ' . ($member['first_name'] ?? '')) ?>
        </h4>
        <div class="small text-muted">#<?= View::e($member['member_number'] ?? '—') ?></div>
    </div>
    <a href="<?= url('boxing/record/' . (int)$member['id']) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kartoteka
    </a>
</div>

<form method="POST" action="<?= url('boxing/record/' . (int)$member['id'] . '/weight/add') ?>" class="card shadow-sm mb-4">
    <?= csrf_field() ?>
    <div class="card-header"><i class="bi bi-plus-circle me-1"></i> Dodaj pomiar</div>
    <div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">Data pomiaru</label>
            <input type="date" class="form-control" name="measured_at" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-2"><label class="form-label">Waga (kg)</label>
            <input type="number" min="0" step="0.01" class="form-control" name="weight_kg" required>
        </div>
        <div class="col-md-4"><label class="form-label">Kategoria wagowa</label>
            <select name="weight_class" class="form-select">
                <option value="">— wybierz —</option>
                <?php foreach ($weightClasses as $code => $label): ?>
                    <option value="<?= View::e($code) ?>"><?= View::e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Notatki</label>
            <input type="text" class="form-control" name="notes" maxlength="200">
        </div>
    </div>
    <div class="card-footer text-end">
        <button class="btn btn-success"><i class="bi bi-save me-1"></i> Dodaj</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-list-check me-1"></i> Historia</div>
    <div class="card-body p-0">
        <?php if (empty($history)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak pomiarow.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Waga (kg)</th><th>Kategoria</th><th>Notatki</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td class="small"><?= View::e($h['measured_at']) ?></td>
                            <td class="font-monospace"><?= View::e(number_format((float)$h['weight_kg'], 2, ',', ' ')) ?></td>
                            <td class="small"><?= View::e($h['weight_class'] ?? '—') ?></td>
                            <td class="small text-muted"><?= View::e($h['notes'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
