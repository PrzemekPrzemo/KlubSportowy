<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy text-primary me-2"></i>Walki — Kickboxing</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resModal">
        <i class="bi bi-plus-circle"></i> Dodaj walkę
    </button>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="<?= url('kickboxing/results') ?>" class="btn btn-sm btn-<?= !$styleFilter ? 'primary' : 'outline-secondary' ?>">Wszystkie</a>
    <?php foreach ($styles as $k => $v): ?>
        <a href="?style=<?= urlencode($k) ?>" class="btn btn-sm btn-<?= $styleFilter === $k ? 'primary' : 'outline-secondary' ?>"><?= View::e($v) ?></a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Zawodnik</th><th>Styl</th><th>Przeciwnik</th><th>Waga</th><th>Wynik</th><th>Sposób</th><th>R.</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Brak walk.</td></tr>
            <?php else: foreach ($results as $r):
                $ri = $resultTypes[$r['result']] ?? ['label' => '—', 'class' => 'secondary'];
            ?>
                <tr>
                    <td class="small"><?= View::e($r['event_date']) ?></td>
                    <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= View::e($styles[$r['style']] ?? $r['style']) ?></span></td>
                    <td><?= View::e($r['opponent_name'] ?? '—') ?></td>
                    <td class="small text-muted"><?= View::e($r['weight_class'] ?? '—') ?></td>
                    <td><?php if ($r['result']): ?><span class="badge bg-<?= $ri['class'] ?>"><?= View::e($ri['label']) ?></span><?php endif; ?></td>
                    <td><small><?= View::e($methods[$r['method']] ?? '—') ?></small></td>
                    <td class="small">
                        <?php if ($r['rounds_fought'] && $r['rounds_total']): ?>
                            <?= (int)$r['rounds_fought'] ?>/<?= (int)$r['rounds_total'] ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('kickboxing/results/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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
            <form method="POST" action="<?= url('kickboxing/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj walkę</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
                        <div class="col-4"><label class="form-label">Styl</label>
                            <select name="style" class="form-select">
                                <?php foreach ($styles as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4"><label class="form-label">Gala / zawody</label>
                            <input type="text" name="event_name" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">Data</label>
                            <input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-4"><label class="form-label">Kategoria wagowa</label>
                            <input type="text" name="weight_class" class="form-control" placeholder="np. -71 kg">
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
                        <div class="col-4"><label class="form-label">Runda walki</label>
                            <input type="number" name="rounds_fought" class="form-control" min="1" max="15">
                        </div>
                        <div class="col-4"><label class="form-label">Rund razem</label>
                            <input type="number" name="rounds_total" class="form-control" min="1" max="15" value="3">
                        </div>
                        <div class="col-4"><label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check mt-3"><input type="checkbox" name="amateur" id="kbam" class="form-check-input" checked><label for="kbam" class="form-check-label">Amatorska</label></div>
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
