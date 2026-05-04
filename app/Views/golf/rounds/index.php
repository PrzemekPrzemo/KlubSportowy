<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-circle-half text-primary me-2"></i>Rundy golfowe</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#rModal">
        <i class="bi bi-plus-circle"></i> Dodaj rundę
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Zawodnik</th><th>Kurs</th><th>Dołki</th><th>Tees</th><th>Uderzenia</th><th>Gross</th><th>Net</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($rounds)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Brak rund.</td></tr>
            <?php else: foreach ($rounds as $r):
                $ti = $tees[$r['tees']] ?? ['label' => $r['tees'], 'color' => '#aaa', 'text' => '#333'];
            ?>
                <tr>
                    <td class="small"><?= View::e($r['round_date']) ?></td>
                    <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                    <td class="small"><?= View::e($r['course_name']) ?></td>
                    <td><?= (int)$r['holes'] ?></td>
                    <td><span class="badge" style="background:<?= $ti['color'] ?>;color:<?= $ti['text'] ?>;border:1px solid #333;"><?= View::e($ti['label']) ?></span></td>
                    <td class="font-monospace"><?= $r['total_strokes'] ?? '—' ?></td>
                    <td class="font-monospace <?= ($r['gross_score'] ?? 0) < 0 ? 'text-success' : '' ?>">
                        <?= $r['gross_score'] !== null ? ($r['gross_score'] > 0 ? '+' : '') . (int)$r['gross_score'] : '—' ?>
                    </td>
                    <td class="font-monospace fw-bold"><?= $r['net_score'] !== null ? number_format((float)$r['net_score'], 1) : '—' ?></td>
                    <td>
                        <form method="POST" action="<?= url('golf/rounds/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="rModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('golf/rounds/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj rundę golfową</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
                        <div class="col-6"><label class="form-label">Data</label>
                            <input type="date" name="round_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-8"><label class="form-label">Kurs (course)</label>
                            <input type="text" name="course_name" class="form-control" required placeholder="np. Pałac Konary Golf Resort">
                        </div>
                        <div class="col-2"><label class="form-label">Dołki</label>
                            <input type="number" name="holes" class="form-control" value="18" min="1" max="18">
                        </div>
                        <div class="col-2"><label class="form-label">Tees</label>
                            <select name="tees" class="form-select">
                                <?php foreach ($tees as $k => $t): ?>
                                    <option value="<?= $k ?>"><?= View::e($t['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3"><label class="form-label">Uderzenia</label>
                            <input type="number" name="total_strokes" class="form-control" min="20" max="200">
                        </div>
                        <div class="col-3"><label class="form-label">Gross (vs par)</label>
                            <input type="number" name="gross_score" class="form-control" placeholder="+5">
                        </div>
                        <div class="col-3"><label class="form-label">Net score</label>
                            <input type="number" step="0.1" name="net_score" class="form-control">
                        </div>
                        <div class="col-3"><label class="form-label">Slope rating</label>
                            <input type="number" name="slope_rating" class="form-control" min="55" max="155" placeholder="113">
                        </div>
                        <div class="col-6"><label class="form-label">Course rating</label>
                            <input type="number" step="0.1" name="course_rating" class="form-control" placeholder="72.1">
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
