<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-book me-1"></i> Biblioteka WOD-ów</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#wodModal"><i class="bi bi-plus-circle"></i> Dodaj WOD klubowy</button>
</div>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Nazwa</th><th>Typ</th><th class="text-end">Time cap (min)</th><th>Zakres</th><th>Opis</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($wods)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak WOD-ów.</td></tr>
        <?php else: foreach ($wods as $w): ?>
            <tr>
                <td><strong><?= View::e($w['name']) ?></strong></td>
                <td><span class="badge bg-info text-dark"><?= View::e($types[$w['type']] ?? $w['type']) ?></span></td>
                <td class="text-end"><?= View::e($w['time_cap_minutes'] ?? '—') ?></td>
                <td>
                    <?php if ($w['club_id'] === null): ?>
                        <span class="badge bg-secondary">Globalny</span>
                    <?php else: ?>
                        <span class="badge bg-primary">Klubowy</span>
                    <?php endif; ?>
                </td>
                <td class="small text-muted"><?= View::e(mb_substr((string)($w['description'] ?? ''), 0, 100)) ?><?= mb_strlen((string)($w['description'] ?? '')) > 100 ? '...' : '' ?></td>
                <td class="text-end">
                    <?php if ($w['club_id'] !== null): ?>
                        <form method="POST" action="<?= url('crossfit/library/' . (int)$w['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć WOD?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="modal fade" id="wodModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= url('crossfit/library/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nowy WOD klubowy</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Nazwa *</label><input type="text" name="name" class="form-control" required></div>
          <div class="mb-2">
            <label class="form-label">Typ</label>
            <select name="type" class="form-select">
              <?php foreach ($types as $k => $l): ?><option value="<?= $k ?>"><?= View::e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Time cap (min)</label><input type="number" name="time_cap_minutes" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Opis</label><textarea name="description" class="form-control" rows="3"></textarea></div>
          <div class="mb-2"><label class="form-label">Scaling rules</label><textarea name="scaling_rules" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
          <button type="submit" class="btn btn-success">Zapisz</button>
        </div>
      </form>
    </div>
  </div>
</div>
