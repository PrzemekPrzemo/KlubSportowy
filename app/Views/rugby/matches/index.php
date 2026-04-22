<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy text-primary me-2"></i>Mecze rugby</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#matchModal">
        <i class="bi bi-plus-circle"></i> Dodaj mecz
    </button>
</div>

<form method="GET" class="mb-3 d-flex gap-2">
    <select name="team" class="form-select form-select-sm" style="width:250px;">
        <option value="">Wszystkie drużyny</option>
        <?php foreach ($teams as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= $teamFilter === (int)$t['id'] ? 'selected' : '' ?>><?= View::e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Gospodarze</th><th>Wynik</th><th>Goście</th><th>Format</th><th>Status</th><th>Miejsce</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($matches)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak meczów.</td></tr>
            <?php else: foreach ($matches as $m):
                $si = $statuses[$m['status']] ?? ['label' => $m['status'], 'class' => 'secondary'];
            ?>
                <tr>
                    <td class="small text-muted"><?= date('Y-m-d H:i', strtotime($m['match_date'])) ?></td>
                    <td><strong><?= View::e($m['home_team_name']) ?></strong></td>
                    <td class="text-center font-monospace fw-bold"><?= (int)$m['home_score'] ?> : <?= (int)$m['away_score'] ?></td>
                    <td><?= View::e($m['away_team_name'] ?? '—') ?></td>
                    <td><span class="badge bg-dark"><?= View::e($formats[$m['format']] ?? $m['format']) ?></span></td>
                    <td><span class="badge bg-<?= $si['class'] ?>"><?= View::e($si['label']) ?></span></td>
                    <td class="small"><?= View::e($m['location'] ?? '—') ?></td>
                    <td>
                        <form method="POST" action="<?= url('rugby/matches/' . (int)$m['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="matchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('rugby/matches/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj mecz</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Gospodarze (drużyna klubu)</label>
                            <select name="home_team_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>"><?= View::e($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Goście (nazwa)</label>
                            <input type="text" name="away_team_name" class="form-control" placeholder="np. AZS AWF Warszawa">
                        </div>
                        <div class="col-6"><label class="form-label">Data i godzina</label>
                            <input type="datetime-local" name="match_date" class="form-control" required>
                        </div>
                        <div class="col-6"><label class="form-label">Miejsce</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">Gospodarze pkt</label>
                            <input type="number" name="home_score" class="form-control" min="0" max="200" value="0">
                        </div>
                        <div class="col-4"><label class="form-label">Goście pkt</label>
                            <input type="number" name="away_score" class="form-control" min="0" max="200" value="0">
                        </div>
                        <div class="col-4"><label class="form-label">Format</label>
                            <select name="format" class="form-select">
                                <?php foreach ($formats as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statuses as $k => $s): ?>
                                    <option value="<?= $k ?>"><?= View::e($s['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">Uwagi</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
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
