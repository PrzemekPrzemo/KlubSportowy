<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-snow2 text-primary me-2"></i>Wyniki — Snowboard</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="<?= url('snowboard/results') ?>" class="btn btn-sm btn-<?= !$discFilter ? 'primary' : 'outline-secondary' ?>">Wszystkie</a>
    <?php foreach ($disciplines as $k => $v): ?>
        <a href="?discipline=<?= urlencode($k) ?>" class="btn btn-sm btn-<?= $discFilter === $k ? 'primary' : 'outline-secondary' ?>"><?= View::e($v) ?></a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Zawodnik</th><th>Dyscyplina</th><th>Zawody</th><th>Run 1</th><th>Run 2</th><th>Best</th><th>#</th><th>FIS</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: foreach ($results as $r): ?>
                <tr class="<?= $r['dnf'] || $r['dns'] ? 'table-warning' : '' ?>">
                    <td class="small"><?= View::e($r['event_date']) ?></td>
                    <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= View::e($disciplines[$r['discipline']] ?? $r['discipline']) ?></span></td>
                    <td class="small"><?= View::e($r['event_name']) ?></td>
                    <td class="font-monospace"><?= $r['run1_score'] !== null ? number_format((float)$r['run1_score'], 2) : '—' ?></td>
                    <td class="font-monospace"><?= $r['run2_score'] !== null ? number_format((float)$r['run2_score'], 2) : '—' ?></td>
                    <td class="font-monospace fw-bold text-success"><?= $r['best_score'] !== null ? number_format((float)$r['best_score'], 2) : '—' ?></td>
                    <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                    <td class="small"><?= $r['fis_points'] !== null ? number_format((float)$r['fis_points'], 2) : '—' ?></td>
                    <td>
                        <form method="POST" action="<?= url('snowboard/results/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="resModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('snowboard/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj wynik snowboard</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Dyscyplina</label>
                            <select name="discipline" class="form-select">
                                <?php foreach ($disciplines as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Zawody</label>
                            <input type="text" name="event_name" class="form-control" required>
                        </div>
                        <div class="col-6"><label class="form-label">Data</label>
                            <input type="date" name="event_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6"><label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control">
                        </div>
                        <div class="col-6"><label class="form-label">Kategoria</label>
                            <input type="text" name="category" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">Run 1 score</label>
                            <input type="number" step="0.01" name="run1_score" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">Run 2 score</label>
                            <input type="number" step="0.01" name="run2_score" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">Miejsce</label>
                            <input type="number" name="place" class="form-control" min="1">
                        </div>
                        <div class="col-6"><label class="form-label">FIS pkt</label>
                            <input type="number" step="0.01" name="fis_points" class="form-control">
                        </div>
                        <div class="col-6">
                            <div class="form-check form-check-inline mt-4"><input type="checkbox" name="dnf" id="sbdnf" class="form-check-input"><label for="sbdnf" class="form-check-label">DNF</label></div>
                            <div class="form-check form-check-inline mt-4"><input type="checkbox" name="dns" id="sbdns" class="form-check-input"><label for="sbdns" class="form-check-label">DNS</label></div>
                        </div>
                        <div class="col-12"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
