<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Pasy — Judo</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#beltModal">
        <i class="bi bi-plus-circle"></i> Nadaj pas
    </button>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Nr</th><th>Pas</th><th>Data nadania</th><th>Egzaminator</th><th>Miejsce</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($belts)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak wpisów.</td></tr>
        <?php else: ?>
            <?php foreach ($belts as $b):
                $bi = $beltMap[$b['belt_level']] ?? ['label' => $b['belt_level'], 'color' => '#aaa'];
            ?>
                <tr>
                    <td><strong><?= View::e($b['last_name']) ?> <?= View::e($b['first_name']) ?></strong></td>
                    <td class="text-muted small"><?= View::e($b['member_number']) ?></td>
                    <td>
                        <span class="badge" style="background:<?= $bi['color'] ?>;color:<?= ($bi['color']==='#000'||$bi['color']==='#8B4513')?'#fff':'#333' ?>;">
                            <?= View::e($bi['label']) ?>
                        </span>
                    </td>
                    <td><?= View::e($b['granted_date']) ?></td>
                    <td><?= View::e($b['examiner'] ?? '—') ?></td>
                    <td><?= View::e($b['location'] ?? '—') ?></td>
                    <td>
                        <form method="POST" action="<?= url('judo/belts/'.(int)$b['id'].'/delete') ?>"
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
            <form method="POST" action="<?= url('judo/belts/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-award me-1"></i> Nadaj pas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik *</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>">
                                    <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                                    (#<?= View::e($m['member_number']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stopień pasa *</label>
                        <select name="belt_level" class="form-select" required>
                            <?php foreach ($beltMap as $key => $info): ?>
                                <option value="<?= $key ?>"><?= View::e($info['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Data nadania *</label>
                            <input type="date" name="granted_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Egzaminator</label>
                            <input type="text" name="examiner" class="form-control" placeholder="Imię i nazwisko">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Miejsce egzaminu</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-award me-1"></i> Nadaj pas</button>
                </div>
            </form>
        </div>
    </div>
</div>
