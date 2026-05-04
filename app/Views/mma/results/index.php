<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy text-primary me-2"></i>Walki MMA</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#rModal">
        <i class="bi bi-plus-circle"></i> Dodaj walkę
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Zawodnik</th><th>Przeciwnik</th><th>Gala</th><th>Wynik</th><th>Sposób</th><th>R/Czas</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak walk.</td></tr>
            <?php else: foreach ($results as $r):
                $ri = $resultTypes[$r['result']] ?? ['label' => '—', 'class' => 'secondary'];
            ?>
                <tr>
                    <td class="small"><?= View::e($r['event_date']) ?></td>
                    <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                    <td><?= View::e($r['opponent_name'] ?? '—') ?></td>
                    <td class="small"><?= View::e($r['event_name']) ?></td>
                    <td><?php if ($r['result']): ?><span class="badge bg-<?= $ri['class'] ?>"><?= View::e($ri['label']) ?></span><?php endif; ?></td>
                    <td><small><?= View::e($methods[$r['method']] ?? '—') ?></small></td>
                    <td class="small font-monospace">
                        <?php if ($r['round']): ?>R<?= (int)$r['round'] ?><?php endif; ?>
                        <?php if ($r['time_s']): ?> · <?= gmdate('i:s', (int)$r['time_s']) ?><?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('mma/results/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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
            <form method="POST" action="<?= url('mma/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj walkę MMA</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
                        <div class="col-6"><label class="form-label">Przeciwnik</label>
                            <input type="text" name="opponent_name" class="form-control">
                        </div>
                        <div class="col-8"><label class="form-label">Gala</label>
                            <input type="text" name="event_name" class="form-control" required>
                        </div>
                        <div class="col-4"><label class="form-label">Data</label>
                            <input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6"><label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control">
                        </div>
                        <div class="col-6"><label class="form-label">Kategoria wagowa</label>
                            <input type="text" name="weight_class" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">Wynik</label>
                            <select name="result" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($resultTypes as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4"><label class="form-label">Sposób</label>
                            <select name="method" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($methods as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-2"><label class="form-label">Runda</label>
                            <input type="number" name="round" class="form-control" min="1" max="5">
                        </div>
                        <div class="col-2"><label class="form-label">Czas (s)</label>
                            <input type="number" name="time_s" class="form-control" min="0" max="900">
                        </div>
                        <div class="col-12">
                            <div class="form-check mt-3"><input type="checkbox" name="amateur" id="maam" class="form-check-input" checked><label for="maam" class="form-check-label">Amatorska</label></div>
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
