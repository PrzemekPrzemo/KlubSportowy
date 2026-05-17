<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-clipboard-pulse me-1"></i> Próby na drogach</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#attemptModal"><i class="bi bi-plus-circle"></i> Nowa próba</button>
</div>

<form method="GET" class="mb-3">
    <div class="row g-2">
        <div class="col-md-4">
            <select name="member_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Wszyscy zawodnicy</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= $filterMemberId === (int)$m['id'] ? 'selected' : '' ?>><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Data</th><th>Zawodnik</th><th>Droga</th><th>Dyscyplina</th><th>Grade</th><th>Result</th><th class="text-end">Próby</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($attempts)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak prób.</td></tr>
        <?php else: foreach ($attempts as $a): ?>
            <?php
              $badge = match ($a['result']) {
                  'top','flash','onsight' => 'success',
                  'zone' => 'warning',
                  'failed' => 'secondary',
                  default => 'light',
              };
            ?>
            <tr>
                <td><?= View::e($a['attempt_date']) ?></td>
                <td><?= View::e($a['last_name']) ?> <?= View::e($a['first_name']) ?></td>
                <td><strong><?= View::e($a['route_name']) ?></strong></td>
                <td><span class="badge bg-secondary"><?= View::e($a['discipline']) ?></span></td>
                <td><?= View::e($a['grade_french'] ?? $a['grade_yds'] ?? '—') ?></td>
                <td><span class="badge bg-<?= $badge ?>"><?= View::e($results[$a['result']] ?? $a['result']) ?></span></td>
                <td class="text-end"><?= (int)$a['attempts_count'] ?></td>
                <td class="text-end">
                    <form method="POST" action="<?= url('climbing/attempts/' . (int)$a['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="attemptModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= url('climbing/attempts/store') ?>">
        <?= csrf_field() ?>
        <div class="modal-header"><h5 class="modal-title">Nowa próba</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Zawodnik *</label>
            <select name="member_id" class="form-select" required>
              <option value="">— wybierz —</option>
              <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Droga *</label>
            <select name="route_id" class="form-select" required>
              <option value="">— wybierz —</option>
              <?php foreach ($routes as $r): ?><option value="<?= (int)$r['id'] ?>"><?= View::e($r['route_name']) ?> (<?= View::e($r['discipline']) ?>, <?= View::e($r['grade_french'] ?? $r['grade_yds'] ?? '?') ?>)</option><?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Data</label><input type="date" name="attempt_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-6">
              <label class="form-label">Result</label>
              <select name="result" class="form-select">
                <?php foreach ($results as $k => $l): ?><option value="<?= $k ?>"><?= View::e($l) ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-2 mt-2"><label class="form-label">Liczba prób</label><input type="number" name="attempts_count" class="form-control" value="1" min="1"></div>
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
