<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-clipboard-data me-1"></i> Szczegóły występu (D/E)</h4>
    <a href="<?= url('gymnastics/scoring') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Powrót</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Data</dt><dd class="col-sm-9"><?= View::e($performance['performed_at']) ?></dd>
            <dt class="col-sm-3">Konkurencja</dt><dd class="col-sm-9"><?= View::e($performance['routine_type'] ?? '—') ?></dd>
            <dt class="col-sm-3">Przyrząd</dt><dd class="col-sm-9"><?= View::e($performance['apparatus'] ?? '—') ?></dd>
            <dt class="col-sm-3">D-score</dt><dd class="col-sm-9"><?= View::e($performance['difficulty_score'] ?? '—') ?></dd>
            <dt class="col-sm-3">E-score</dt><dd class="col-sm-9"><?= View::e($performance['execution_score'] ?? '—') ?></dd>
            <dt class="col-sm-3">Deductions</dt><dd class="col-sm-9"><?= View::e($performance['deductions']) ?></dd>
            <dt class="col-sm-3">Total</dt><dd class="col-sm-9"><strong><?= View::e($performance['total_score'] ?? '—') ?></strong></dd>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Panel sędziowski</strong>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#judgeModal">
            <i class="bi bi-plus-circle"></i> Dodaj sędziego
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>Sędzia</th><th>Certyfikacja</th><th>Kategoria</th><th class="text-end">Ocena</th></tr>
            </thead>
            <tbody>
            <?php if (empty($judges)): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">Brak ocen.</td></tr>
            <?php else: foreach ($judges as $j): ?>
                <tr>
                    <td><?= View::e($j['judge_name']) ?></td>
                    <td><?= View::e($j['judge_certification'] ?? '—') ?></td>
                    <td><?= View::e($j['score_category'] ?? '—') ?></td>
                    <td class="text-end"><strong><?= View::e($j['score_value']) ?></strong></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="judgeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= url('gymnastics/scoring/' . (int)$performance['id'] . '/judge') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Dodaj ocenę sędziego</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Imię sędziego *</label><input type="text" name="judge_name" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Certyfikacja</label><input type="text" name="judge_certification" class="form-control" placeholder="FIG, krajowa..."></div>
          <div class="mb-2"><label class="form-label">Kategoria</label><input type="text" name="score_category" class="form-control" placeholder="difficulty / execution"></div>
          <div class="mb-2"><label class="form-label">Ocena *</label><input type="number" step="0.01" name="score_value" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Notatki</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
          <button type="submit" class="btn btn-success">Zapisz</button>
        </div>
      </form>
    </div>
  </div>
</div>
