<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-list-ol text-primary me-2"></i>Szermierze — ranking klubu</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#fencerModal">
        <i class="bi bi-plus-circle"></i> Dodaj/edytuj profil
    </button>
</div>

<div class="mb-3 d-flex gap-2">
    <a href="<?= url('fencing/fencers') ?>" class="btn btn-sm btn-<?= !$weaponFilter ? 'primary' : 'outline-secondary' ?>">Wszystkie bronie</a>
    <?php foreach ($weapons as $k => $w): ?>
        <a href="?weapon=<?= urlencode($k) ?>" class="btn btn-sm btn-<?= $weaponFilter === $k ? 'primary' : 'outline-secondary' ?>" style="<?= $weaponFilter === $k ? '' : 'border-color:' . $w['color'] . ';color:' . $w['color'] . ';' ?>">
            <?= View::e($w['label']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:60px">Poz.</th>
                    <th>Szermierz</th>
                    <th>Broń</th>
                    <th>FIE ID</th>
                    <th>Stronność</th>
                    <th class="text-center">Punkty rankingowe</th>
                    <th class="text-center">Starty</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($fencers)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak szermierzy.</td></tr>
            <?php else: foreach ($fencers as $f):
                $w = $weapons[$f['primary_weapon']] ?? ['label' => $f['primary_weapon'], 'color' => '#aaa'];
            ?>
                <tr>
                    <td class="text-center">
                        <?php if ($f['position'] === 1): ?>
                            <i class="bi bi-trophy-fill text-warning fs-5"></i>
                        <?php else: ?>
                            <span class="badge bg-secondary">#<?= (int)$f['position'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= View::e($f['last_name'] . ' ' . $f['first_name']) ?></strong>
                        <small class="text-muted d-block">#<?= View::e($f['member_number']) ?></small>
                    </td>
                    <td>
                        <span class="badge" style="background:<?= $w['color'] ?>;color:#fff;">
                            <?= View::e($w['label']) ?>
                        </span>
                    </td>
                    <td><small class="font-monospace text-muted"><?= View::e($f['fie_id'] ?? '—') ?></small></td>
                    <td><small><?= View::e($lateralities[$f['laterality']] ?? $f['laterality']) ?></small></td>
                    <td class="text-center"><span class="badge bg-primary fs-6"><?= (int)$f['ranking_points'] ?></span></td>
                    <td class="text-center"><?= (int)$f['total_starts'] ?></td>
                    <td>
                        <form method="POST" action="<?= url('fencing/fencers/' . (int)$f['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć profil?')">
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

<div class="modal fade" id="fencerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('fencing/fencers/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Profil szermierza</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="alert alert-info small">Jeśli szermierz już istnieje, wpis zostanie zaktualizowany.</div>
                    <div class="mb-3">
                        <label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $mm): ?>
                                <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Broń podstawowa</label>
                            <select name="primary_weapon" class="form-select">
                                <?php foreach ($weapons as $k => $w): ?>
                                    <option value="<?= $k ?>"><?= View::e($w['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Stronność</label>
                            <select name="laterality" class="form-select">
                                <?php foreach ($lateralities as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">FIE ID (opcjonalny)</label>
                            <input type="text" name="fie_id" class="form-control" placeholder="np. 12345">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Wzrost (cm)</label>
                            <input type="number" name="height_cm" class="form-control" min="100" max="220">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Punkty rankingowe</label>
                        <input type="number" name="ranking_points" class="form-control" min="0" value="0">
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
