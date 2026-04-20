<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Pary i ranking — Padel</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pairModal">
        <i class="bi bi-plus-circle"></i> Dodaj parę
    </button>
</div>

<div class="d-flex gap-2 mb-3">
    <a href="<?= url('padel/pairs') ?>" class="btn btn-sm <?= empty($filterCat) ? 'btn-primary' : 'btn-outline-primary' ?>">Wszystkie</a>
    <a href="<?= url('padel/pairs?category=men') ?>" class="btn btn-sm <?= $filterCat==='men' ? 'btn-primary' : 'btn-outline-secondary' ?>">Mężczyźni</a>
    <a href="<?= url('padel/pairs?category=women') ?>" class="btn btn-sm <?= $filterCat==='women' ? 'btn-primary' : 'btn-outline-secondary' ?>">Kobiety</a>
    <a href="<?= url('padel/pairs?category=mixed') ?>" class="btn btn-sm <?= $filterCat==='mixed' ? 'btn-primary' : 'btn-outline-secondary' ?>">Mikst</a>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>#</th><th>Para</th><th>Zawodnicy</th><th>Kategoria</th><th class="text-center">Punkty</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($pairs)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak par.</td></tr>
        <?php else: ?>
            <?php foreach ($pairs as $i => $p): ?>
            <tr>
                <td class="text-muted"><?= $i + 1 ?></td>
                <td><strong><?= View::e($p['pair_name'] ?? $p['p1_last'] . '/' . $p['p2_last']) ?></strong></td>
                <td><?= View::e($p['p1_last'] . ' ' . $p['p1_first']) ?> &amp; <?= View::e($p['p2_last'] . ' ' . $p['p2_first']) ?></td>
                <td>
                    <?php $catLabel=['men'=>'Mężczyźni','women'=>'Kobiety','mixed'=>'Mikst']; ?>
                    <span class="badge bg-info text-dark"><?= $catLabel[$p['category']] ?? $p['category'] ?></span>
                </td>
                <td class="text-center fw-bold"><?= (int)$p['ranking_points'] ?></td>
                <td>
                    <form method="POST" action="<?= url('padel/pairs/' . (int)$p['id'] . '/delete') ?>"
                          onsubmit="return confirm('Usunąć parę?')">
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

<!-- Modal: Dodaj parę -->
<div class="modal fade" id="pairModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('padel/pairs/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-people me-1"></i> Nowa para</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik 1</label>
                        <select name="player1_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zawodnik 2</label>
                        <select name="player2_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Nazwa pary (opcjonalnie)</label>
                            <input type="text" name="pair_name" class="form-control">
                        </div>
                        <div class="col-3">
                            <label class="form-label">Kategoria</label>
                            <select name="category" class="form-select">
                                <option value="mixed">Mikst</option>
                                <option value="men">Mężczyźni</option>
                                <option value="women">Kobiety</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label">Punkty</label>
                            <input type="number" name="ranking_points" class="form-control" value="0" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Dodaj parę</button>
                </div>
            </form>
        </div>
    </div>
</div>
