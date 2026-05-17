<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-person-vcard me-1"></i> Profile żeglarzy (ISAF)</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="bi bi-plus-circle"></i> Zapisz profil</button>
</div>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Klasy łodzi</th><th>Numer ISAF</th><th class="text-end">Ranking</th></tr>
        </thead>
        <tbody>
        <?php if (empty($profiles)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Brak profili.</td></tr>
        <?php else: foreach ($profiles as $p): ?>
            <tr>
                <td><strong><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></strong></td>
                <td>
                    <?php foreach (explode(',', (string)$p['boat_classes']) as $c): if (!$c) continue; ?>
                        <span class="badge bg-info text-dark"><?= View::e($boatClasses[$c] ?? $c) ?></span>
                    <?php endforeach; ?>
                </td>
                <td><?= View::e($p['isaf_number'] ?? '—') ?></td>
                <td class="text-end"><?= $p['national_rank'] !== null ? View::e($p['national_rank']) : '—' ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= url('sailing/sailor/save') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Profil żeglarza</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Zawodnik *</label>
            <select name="member_id" class="form-select" required>
              <option value="">— wybierz —</option>
              <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Klasy łodzi</label>
            <div>
              <?php foreach ($boatClasses as $k => $l): ?>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="checkbox" name="boat_classes[]" value="<?= $k ?>" id="bc_<?= $k ?>">
                  <label class="form-check-label" for="bc_<?= $k ?>"><?= View::e($l) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="mb-2"><label class="form-label">Numer ISAF</label><input type="text" name="isaf_number" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Ranking krajowy</label><input type="number" name="national_rank" class="form-control"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
          <button type="submit" class="btn btn-success">Zapisz</button>
        </div>
      </form>
    </div>
  </div>
</div>
