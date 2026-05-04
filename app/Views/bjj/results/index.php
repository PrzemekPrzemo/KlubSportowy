<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki walk — BJJ</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<!-- Filtry -->
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label small">Gi / No-Gi</label>
            <select name="gi" class="form-select form-select-sm">
                <option value="">Wszystkie</option>
                <option value="gi"   <?= ($filterGi ?? '') === 'gi'   ? 'selected' : '' ?>>Gi</option>
                <option value="nogi" <?= ($filterGi ?? '') === 'nogi' ? 'selected' : '' ?>>No-Gi</option>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small">Rok</label>
            <input type="number" name="year" class="form-control form-control-sm" style="width:90px"
                   value="<?= View::e((string)($filterYear ?? '')) ?>" placeholder="<?= date('Y') ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filtruj</button>
        </div>
    </form>
</div>

<?php
$total = count($results);
$wins  = count(array_filter($results, fn($r) => $r['result'] === 'win'));
$losses= count(array_filter($results, fn($r) => $r['result'] === 'loss'));
$draws = count(array_filter($results, fn($r) => $r['result'] === 'draw'));
?>
<?php if ($total > 0): ?>
<div class="row g-2 mb-3">
    <div class="col-auto"><span class="badge bg-success fs-6"><?= $wins ?> W</span></div>
    <div class="col-auto"><span class="badge bg-danger fs-6"><?= $losses ?> L</span></div>
    <div class="col-auto"><span class="badge bg-secondary fs-6"><?= $draws ?> D</span></div>
    <div class="col-auto text-muted small align-self-center">łącznie <?= $total ?> walk</div>
</div>
<?php endif; ?>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Zawodnik</th><th>Zawody</th><th>Data</th><th>Wynik</th>
                <th>Metoda</th><th>Waga</th><th>Gi</th><th>Miejsce</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($results)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak wyników.</td></tr>
        <?php else: ?>
            <?php foreach ($results as $r): ?>
            <tr>
                <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                <td><?= View::e($r['event_name']) ?><?php if(!empty($r['opponent'])): ?><br><small class="text-muted">vs <?= View::e($r['opponent']) ?></small><?php endif; ?></td>
                <td><?= View::e($r['event_date']) ?></td>
                <td>
                    <?php $badges=['win'=>'success','loss'=>'danger','draw'=>'secondary','dq'=>'warning']; ?>
                    <span class="badge bg-<?= $badges[$r['result']] ?? 'secondary' ?>">
                        <?= strtoupper($r['result']) ?>
                    </span>
                </td>
                <td><small><?= View::e($r['method'] ?? '—') ?></small></td>
                <td><?= View::e($r['weight_category'] ?? '—') ?></td>
                <td><small><?= strtoupper($r['gi'] ?? '') ?></small></td>
                <td><?= $r['placement'] ? '#' . (int)$r['placement'] : '—' ?></td>
                <td>
                    <form method="POST" action="<?= url('bjj/results/' . (int)$r['id'] . '/delete') ?>"
                          onsubmit="return confirm('Usunąć wynik?')">
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

<!-- Modal: Dodaj wynik -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('bjj/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trophy me-1"></i> Dodaj wynik walki</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Zawody</label>
                            <input type="text" name="event_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data</label>
                            <input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Wynik</label>
                            <select name="result" class="form-select" required>
                                <option value="win">Wygrana (Win)</option>
                                <option value="loss">Przegrana (Loss)</option>
                                <option value="draw">Remis (Draw)</option>
                                <option value="dq">Dyskwalifikacja (DQ)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Metoda</label>
                            <select name="method" class="form-select">
                                <option value="">—</option>
                                <option value="submission">Submission</option>
                                <option value="points">Points</option>
                                <option value="decision">Decision</option>
                                <option value="referee">Referee</option>
                                <option value="walkover">Walkover</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kategoria wagowa</label>
                            <select name="weight_category" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($weights as $w): ?>
                                    <option value="<?= $w ?>"><?= $w ?> kg</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gi / No-Gi</label>
                            <select name="gi" class="form-select">
                                <option value="gi">Gi</option>
                                <option value="nogi">No-Gi</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Miejsce</label>
                            <input type="number" name="placement" class="form-control" min="1" placeholder="np. 1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Przeciwnik</label>
                            <input type="text" name="opponent" class="form-control" placeholder="Opcjonalnie">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uwagi</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zapisz wynik</button>
                </div>
            </form>
        </div>
    </div>
</div>
