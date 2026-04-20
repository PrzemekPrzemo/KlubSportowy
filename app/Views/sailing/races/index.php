<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Regaty i wyniki — Żeglarstwo</h4>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2">
            <input type="number" name="year" class="form-control form-control-sm" style="width:90px" value="<?= $filterYear ?>">
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
        </form>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#raceModal">
            <i class="bi bi-plus-circle"></i> Dodaj
        </button>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Harmonogram <?= $filterYear ?></h6></div>
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Nazwa</th><th>Data</th><th>Typ</th><th>Dystans</th><th></th></tr>
                </thead>
                <tbody>
                <?php if (empty($races)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Brak regatów.</td></tr>
                <?php else: ?>
                    <?php foreach ($races as $r): ?>
                    <tr>
                        <td><strong><?= View::e($r['name']) ?></strong>
                            <?php if ($r['location']): ?><small class="text-muted d-block"><?= View::e($r['location']) ?></small><?php endif; ?>
                        </td>
                        <td><?= View::e($r['race_date']) ?></td>
                        <td><span class="badge bg-info text-dark"><?= ucfirst($r['race_type']) ?></span></td>
                        <td><?= $r['distance_nm'] ? $r['distance_nm'] . ' Mm' : '—' ?></td>
                        <td>
                            <form method="POST" action="<?= url('sailing/races/' . (int)$r['id'] . '/delete') ?>"
                                  onsubmit="return confirm('Usunąć?')">
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
    </div>
    <div class="col-md-5">
        <?php if (!empty($standings)): ?>
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Klasyfikacja sezonowa <?= $filterYear ?></h6></div>
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>#</th><th>Łódź</th><th class="text-center">Starty</th><th class="text-center">Pkt</th></tr></thead>
                <tbody>
                <?php foreach ($standings as $i => $s): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= View::e($s['boat_name']) ?></td>
                        <td class="text-center"><?= $s['races'] ?></td>
                        <td class="text-center fw-bold"><?= $s['points'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Dodaj regatę -->
<div class="modal fade" id="raceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('sailing/races/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trophy me-1"></i> Nowa regata / rejs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nazwa</label><input type="text" name="name" class="form-control" required></div>
                    <div class="row g-2 mb-3">
                        <div class="col-4"><label class="form-label">Data</label><input type="date" name="race_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-4"><label class="form-label">Typ</label>
                            <select name="race_type" class="form-select">
                                <option value="regata">Regata</option>
                                <option value="rejs">Rejs</option>
                                <option value="zawody">Zawody</option>
                                <option value="trening">Trening</option>
                            </select>
                        </div>
                        <div class="col-4"><label class="form-label">Dystans (Mm)</label><input type="number" name="distance_nm" class="form-control" step="0.1" min="0"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Lokalizacja</label><input type="text" name="location" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
