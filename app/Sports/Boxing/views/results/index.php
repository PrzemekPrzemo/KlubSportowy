<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy text-warning me-2"></i>Walki — Boks</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#fightModal">
        <i class="bi bi-plus-circle"></i> Dodaj walkę
    </button>
</div>

<!-- Club record summary -->
<?php if (!empty($clubRecord)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <i class="bi bi-people-fill me-1"></i> Rekord zawodników klubu
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>Zawodnik</th><th class="text-center text-success">W</th><th class="text-center text-danger">L</th><th class="text-center">D</th><th class="text-center">Walki</th><th class="text-center">Rekord</th></tr>
            </thead>
            <tbody>
                <?php foreach ($clubRecord as $r): ?>
                    <tr>
                        <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong> <small class="text-muted">#<?= View::e($r['member_number']) ?></small></td>
                        <td class="text-center text-success fw-bold"><?= (int)$r['wins'] ?></td>
                        <td class="text-center text-danger"><?= (int)$r['losses'] ?></td>
                        <td class="text-center"><?= (int)$r['draws'] ?></td>
                        <td class="text-center"><?= (int)$r['total'] ?></td>
                        <td class="text-center font-monospace fw-bold">
                            <?= (int)$r['wins'] ?>-<?= (int)$r['losses'] ?>-<?= (int)$r['draws'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Fight list -->
<div class="card shadow-sm">
    <div class="card-header">Historia walk</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th><th>Zawodnik</th><th>Przeciwnik</th><th>Wynik</th><th>Sposób</th>
                    <th>Runda/Łącznie</th><th>Waga</th><th>Zawody</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Brak walk.</td></tr>
            <?php else: foreach ($results as $r):
                $resInfo = $resultTypes[$r['result']] ?? ['label' => '—', 'class' => 'secondary'];
            ?>
                <tr>
                    <td class="small text-muted"><?= View::e($r['competition_date']) ?></td>
                    <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                    <td><?= View::e($r['opponent_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($r['result']): ?>
                            <span class="badge bg-<?= $resInfo['class'] ?>"><?= View::e($resInfo['label']) ?></span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= View::e($methods[$r['method']] ?? '—') ?></small></td>
                    <td class="small">
                        <?php if ($r['rounds_fought'] && $r['rounds_total']): ?>
                            R<?= (int)$r['rounds_fought'] ?>/<?= (int)$r['rounds_total'] ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= View::e($r['weight_class'] ?? '—') ?></small></td>
                    <td><small><?= View::e($r['competition_name']) ?></small></td>
                    <td>
                        <form method="POST" action="<?= url('boxing/results/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<!-- Modal -->
<div class="modal fade" id="fightModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('boxing/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj walkę</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Przeciwnik</label>
                            <input type="text" name="opponent_name" class="form-control" placeholder="Imię i nazwisko">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Data walki</label>
                            <input type="date" name="competition_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Zawody / Gala</label>
                            <input type="text" name="competition_name" class="form-control" placeholder="Nazwa gali">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Kategoria wiekowa</label>
                            <select name="category" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($categories as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Kategoria wagowa</label>
                            <select name="weight_class" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($weightClasses as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Wynik</label>
                            <select name="result" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($resultTypes as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Sposób</label>
                            <select name="method" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($methods as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Runda walki</label>
                            <input type="number" name="rounds_fought" class="form-control" min="1" max="15">
                        </div>
                        <div class="col-3">
                            <label class="form-label">Łącznie rund</label>
                            <input type="number" name="rounds_total" class="form-control" min="1" max="15" value="3">
                        </div>
                        <div class="col-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="amateur" id="amateurChk" class="form-check-input" checked>
                                <label class="form-check-label" for="amateurChk">Walka amatorska</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uwagi</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
