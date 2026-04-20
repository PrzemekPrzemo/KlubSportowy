<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Pasy — Brazilian Jiu-Jitsu</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#beltModal">
        <i class="bi bi-plus-circle"></i> Nadaj pas
    </button>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Zawodnik</th><th>Nr</th><th>Pas</th><th>Stripes</th>
                <th>Gi/No-Gi</th><th>Data egzaminu</th><th>Egzaminator</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($belts)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak wpisów.</td></tr>
        <?php else: ?>
            <?php foreach ($belts as $b):
                $bi = $beltMap[$b['belt_color']] ?? ['label' => $b['belt_color'], 'color' => '#aaa', 'text' => '#333'];
            ?>
            <tr>
                <td><strong><?= View::e($b['last_name']) ?> <?= View::e($b['first_name']) ?></strong></td>
                <td class="text-muted small"><?= View::e($b['member_number']) ?></td>
                <td>
                    <span class="badge border" style="background:<?= $bi['color'] ?>;color:<?= $bi['text'] ?>;">
                        <?= View::e($bi['label']) ?>
                    </span>
                </td>
                <td>
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <i class="bi bi-<?= $i < (int)$b['stripes'] ? 'dash-square-fill text-warning' : 'dash-square text-muted' ?>"></i>
                    <?php endfor; ?>
                </td>
                <td><span class="badge bg-<?= $b['gi'] === 'gi' ? 'info' : ($b['gi'] === 'nogi' ? 'secondary' : 'primary') ?> text-dark">
                    <?= strtoupper($b['gi']) ?>
                </span></td>
                <td><?= View::e($b['exam_date']) ?></td>
                <td><?= View::e($b['examiner'] ?? '—') ?></td>
                <td>
                    <form method="POST" action="<?= url('bjj/belts/' . (int)$b['id'] . '/delete') ?>"
                          onsubmit="return confirm('Usunąć wpis?')">
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

<!-- Modal: Nadaj pas -->
<div class="modal fade" id="beltModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('bjj/belts/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-award me-1"></i> Nadaj pas BJJ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Pas</label>
                            <select name="belt_color" class="form-select" required>
                                <?php foreach ($beltMap as $key => $b): ?>
                                    <option value="<?= $key ?>"><?= View::e($b['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Stripes (0–4)</label>
                            <input type="number" name="stripes" class="form-control" min="0" max="4" value="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label">Gi / No-Gi</label>
                            <select name="gi" class="form-select">
                                <option value="gi">Gi</option>
                                <option value="nogi">No-Gi</option>
                                <option value="both">Oba</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Data egzaminu</label>
                            <input type="date" name="exam_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Egzaminator</label>
                            <input type="text" name="examiner" class="form-control" placeholder="Imię i nazwisko">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uwagi</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Nadaj pas</button>
                </div>
            </form>
        </div>
    </div>
</div>
