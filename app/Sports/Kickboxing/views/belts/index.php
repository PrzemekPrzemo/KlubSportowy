<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-award text-primary me-2"></i>Pasy — Kickboxing</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#beltModal">
        <i class="bi bi-plus-circle"></i> Nadaj pas
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Zawodnik</th><th>Nr</th><th>Pas</th><th>Dan</th><th>Data</th><th>Egzaminator</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($belts)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak wpisów.</td></tr>
            <?php else: foreach ($belts as $b):
                $bi = $beltMap[$b['belt_color']] ?? ['label' => $b['belt_color'], 'color' => '#aaa'];
            ?>
                <tr>
                    <td><strong><?= View::e($b['last_name'] . ' ' . $b['first_name']) ?></strong></td>
                    <td class="small text-muted"><?= View::e($b['member_number']) ?></td>
                    <td><span class="badge border" style="background:<?= $bi['color'] ?>;color:<?= $bi['color'] === '#ffffff' ? '#333' : '#fff' ?>;"><?= View::e($bi['label']) ?></span></td>
                    <td><?php if ((int)$b['dan'] > 0): ?><span class="badge bg-dark"><?= (int)$b['dan'] ?> dan</span><?php endif; ?></td>
                    <td class="small"><?= View::e($b['exam_date']) ?></td>
                    <td class="small text-muted"><?= View::e($b['examiner'] ?? '—') ?></td>
                    <td>
                        <form method="POST" action="<?= url('kickboxing/belts/' . (int)$b['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="beltModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('kickboxing/belts/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nadaj pas kickboxing</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $mm): ?>
                                <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7"><label class="form-label">Pas</label>
                            <select name="belt_color" class="form-select" required>
                                <?php foreach ($beltMap as $k => $b): ?>
                                    <option value="<?= $k ?>"><?= View::e($b['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5"><label class="form-label">Dan (0-10)</label>
                            <input type="number" name="dan" class="form-control" min="0" max="10" value="0">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Data egzaminu</label>
                            <input type="date" name="exam_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6"><label class="form-label">Egzaminator</label>
                            <input type="text" name="examiner" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Nadaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
