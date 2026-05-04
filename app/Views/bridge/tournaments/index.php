<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy text-primary me-2"></i>Turnieje brydża</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tModal">
        <i class="bi bi-plus-circle"></i> Dodaj turniej
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Nazwa</th><th>Typ</th><th>Uczestnicy</th><th>Miejsce</th><th>MP</th><th>IMP</th><th>PZBS pkt</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($tournaments)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Brak turniejów.</td></tr>
            <?php else: foreach ($tournaments as $t): ?>
                <tr>
                    <td class="small"><?= View::e($t['tournament_date']) ?></td>
                    <td><strong><?= View::e($t['name']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= View::e($types[$t['tournament_type']] ?? $t['tournament_type']) ?></span></td>
                    <td class="small">
                        <?php if ($t['partnership_name']): ?><?= View::e($t['partnership_name']) ?><?php endif; ?>
                        <?php if ($t['member_last']): ?><?= View::e($t['member_last'] . ' ' . $t['member_first']) ?><?php endif; ?>
                    </td>
                    <td><?php if ($t['place']): ?><span class="badge bg-primary">#<?= (int)$t['place'] ?></span><?php endif; ?></td>
                    <td class="font-monospace"><?= $t['score_mp']  !== null ? number_format((float)$t['score_mp'], 2)  : '—' ?></td>
                    <td class="font-monospace"><?= $t['score_imp'] !== null ? number_format((float)$t['score_imp'], 2) : '—' ?></td>
                    <td class="font-monospace fw-bold"><?= $t['pzbs_points'] !== null ? number_format((float)$t['pzbs_points'], 2) : '—' ?></td>
                    <td>
                        <form method="POST" action="<?= url('bridge/tournaments/' . (int)$t['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="tModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('bridge/tournaments/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj turniej</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-8"><label class="form-label">Nazwa turnieju</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-4"><label class="form-label">Typ</label>
                            <select name="tournament_type" class="form-select">
                                <?php foreach ($types as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Data</label>
                            <input type="date" name="tournament_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6"><label class="form-label">Miejsce</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                        <div class="col-6"><label class="form-label">Para (turniej parowy)</label>
                            <select name="partnership_id" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($partnerships as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"><?= View::e(($p['name'] ?? $p['p1_last'] . ' / ' . $p['p2_last'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Gracz (turniej indywidualny)</label>
                            <select name="member_id" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3"><label class="form-label">Miejsce</label>
                            <input type="number" name="place" class="form-control" min="1">
                        </div>
                        <div class="col-3"><label class="form-label">MP score</label>
                            <input type="number" step="0.01" name="score_mp" class="form-control">
                        </div>
                        <div class="col-3"><label class="form-label">IMP score</label>
                            <input type="number" step="0.01" name="score_imp" class="form-control">
                        </div>
                        <div class="col-3"><label class="form-label">PZBS pkt</label>
                            <input type="number" step="0.01" name="pzbs_points" class="form-control">
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
