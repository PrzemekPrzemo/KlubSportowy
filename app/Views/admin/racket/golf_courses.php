<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-flag me-2"></i>Golf — Pola</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#courseModal">
        <i class="bi bi-plus-circle"></i> Dodaj pole
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nazwa</th><th>Miasto</th><th>Dołki</th><th>Par</th>
                    <th>Rating</th><th>Slope</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($courses)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak pól.</td></tr>
            <?php else: foreach ($courses as $c): ?>
                <tr>
                    <td><strong><?= View::e($c['name']) ?></strong></td>
                    <td><?= View::e($c['city'] ?? '—') ?></td>
                    <td><?= (int)$c['holes_count'] ?></td>
                    <td><?= (int)$c['par_total'] ?></td>
                    <td><?= $c['rating'] !== null ? View::e((string)$c['rating']) : '—' ?></td>
                    <td><?= $c['slope'] !== null ? (int)$c['slope'] : '—' ?></td>
                    <td>
                        <form method="POST" action="<?= url('club/sport/golf/courses/' . (int)$c['id'] . '/delete') ?>"
                              onsubmit="return confirm('Usunąć pole?')">
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

<div class="modal fade" id="courseModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= url('club/sport/golf/courses/store') ?>" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Nowe pole golfowe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Nazwa *</label>
                    <input name="name" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Miasto</label>
                    <input name="city" class="form-control"></div>
                <div class="row g-2">
                    <div class="col-4"><label class="form-label">Dołków</label>
                        <input type="number" name="holes_count" value="18" min="9" max="36" class="form-control"></div>
                    <div class="col-4"><label class="form-label">Par</label>
                        <input type="number" name="par_total" value="72" min="30" max="150" class="form-control"></div>
                    <div class="col-4"><label class="form-label">Rating</label>
                        <input type="number" step="0.1" name="rating" class="form-control"></div>
                </div>
                <div class="mb-2"><label class="form-label">Slope (55..155)</label>
                    <input type="number" name="slope" min="55" max="155" class="form-control"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                <button class="btn btn-success">Zapisz</button>
            </div>
        </form>
    </div>
</div>
